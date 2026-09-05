<?php

namespace App\Services;

use App\Enums\LessonQuizStatus;
use App\Enums\SettingKey;
use App\Models\Lesson;
use App\Models\LessonQuiz;
use App\Models\Setting;
use App\Services\OpenAI\ModerationClient;
use App\Services\OpenAI\QuizGenerationClient;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

class QuizGenerationService
{
    public function __construct(
        private readonly QuizGenerationClient $client,
        private readonly ModerationClient $moderationClient,
    ) {}

    /**
     * Generate (or regenerate) a lesson's quiz from its slide text, falling
     * back to Reading Materials only when no slide text has been pasted in
     * yet. Regeneration always replaces the quiz's existing questions —
     * callers (the Filament "Generate Quiz" action) are responsible for
     * confirming that with the admin first, since this never prompts.
     */
    public function generate(Lesson $lesson, int $questionCount = 5): LessonQuiz
    {
        $sourceText = $this->resolveSourceText($lesson);

        if ($sourceText === '') {
            throw new RuntimeException(
                'Cannot generate a quiz: this lesson has no slide text and no reading content to generate from.'
            );
        }

        // Checked before spending an API call. Only successful generations
        // increment the counter (see below), so a moderation rejection or
        // an outage can't permanently strand a quiz with zero questions.
        $existingQuiz = $lesson->quiz;

        if ($existingQuiz && ! $existingQuiz->hasGenerationsRemaining()) {
            $limit = Setting::get(SettingKey::QuizMaxGenerations);

            throw new RuntimeException(
                "This quiz has already been generated {$existingQuiz->generation_count} times, which is the "
                ."configured limit of {$limit}. Edit the questions by hand, or raise the limit in Settings."
            );
        }

        $gradeBand = $lesson->module->course->grade_band;

        $raw = $this->client->generateQuestions(
            sourceText: $sourceText,
            gradeBand: $gradeBand,
            questionCount: $questionCount,
            lessonTitle: $lesson->title,
            moduleTitle: $lesson->module->title,
        );

        $questions = $this->validateQuestions($raw);

        $this->assertQuestionsAreSafeForMinors($questions, $lesson);

        $sourceHash = $this->sourceHash($lesson, $sourceText, $gradeBand);

        return DB::transaction(function () use ($lesson, $sourceHash, $questions, $existingQuiz) {
            $quiz = $lesson->quiz()->updateOrCreate(
                ['locale' => 'en'],
                [
                    'status' => LessonQuizStatus::Draft,
                    'generated_at' => now(),
                    // Incremented here rather than up front so only
                    // generations the admin actually got questions out of
                    // count against the limit.
                    'generation_count' => ($existingQuiz?->generation_count ?? 0) + 1,
                    'source_text_hash' => $sourceHash,
                ],
            );

            $quiz->questions()->delete();

            foreach ($questions as $index => $question) {
                $quiz->questions()->create([
                    'question_text' => $question['question_text'],
                    'type' => 'multiple_choice',
                    'options' => $question['options'],
                    'correct_answer' => $question['correct_answer'],
                    'explanation' => $question['explanation'],
                    'is_ai_generated' => true,
                    'sort_order' => $index,
                ]);
            }

            return $quiz->fresh('questions');
        });
    }

    /**
     * The prompt asks for age-appropriate content, but a request isn't a
     * guarantee — this is the actual enforcement. Everything the student
     * would read (questions, options, explanations) goes through the same
     * moderation endpoint used for Community chat, and anything flagged
     * fails the whole generation rather than landing in a draft an admin
     * might skim past.
     *
     * Sent as one combined call rather than per-question: regeneration is
     * all-or-nothing anyway, so pinpointing the offending question buys
     * nothing, and one request keeps generation responsive.
     *
     * @param  list<array{question_text: string, options: list<string>, correct_answer: string, explanation: string}>  $questions
     */
    private function assertQuestionsAreSafeForMinors(array $questions, Lesson $lesson): void
    {
        $text = collect($questions)
            ->flatMap(fn (array $question) => [
                $question['question_text'],
                $question['explanation'],
                ...$question['options'],
            ])
            ->implode("\n");

        try {
            $result = $this->moderationClient->moderate($text);
        } catch (Throwable $e) {
            // Fail closed, matching ModerateMessageJob: unreviewed content
            // for minors is not something to wave through on a transient
            // error. The admin can simply retry.
            Log::error('Quiz generation moderation check failed; discarding the generated questions.', [
                'lesson_id' => $lesson->id,
                'error' => $e->getMessage(),
            ]);

            throw new RuntimeException(
                'Could not safety-check the generated questions, so they were discarded. Please try again.',
                previous: $e,
            );
        }

        if ($result['flagged']) {
            Log::warning('Quiz generation produced content flagged by moderation; discarding it.', [
                'lesson_id' => $lesson->id,
                'categories' => $result['categories'],
            ]);

            $categories = $result['categories'] === []
                ? 'unspecified'
                : implode(', ', $result['categories']);

            throw new RuntimeException(
                "The generated questions were flagged as unsuitable for students ({$categories}) and were discarded. "
                .'Try generating again, or review the slide text for content that may have prompted this.'
            );
        }
    }

    /**
     * Fingerprint everything that feeds the prompt, not just the slide text —
     * retitling a lesson or moving it to another grade band changes the
     * questions we'd generate, so it should trip the "source has changed"
     * warning on the edit page just as editing the slides does.
     */
    public function sourceHash(Lesson $lesson, ?string $sourceText = null, ?string $gradeBand = null): string
    {
        return hash('sha256', implode("\0", [
            $sourceText ?? $this->resolveSourceText($lesson),
            $gradeBand ?? $lesson->module->course->grade_band,
            (string) $lesson->title,
            (string) $lesson->module->title,
        ]));
    }

    private function resolveSourceText(Lesson $lesson): string
    {
        $slidesText = trim((string) $lesson->slideshow?->slides_text);

        if ($slidesText !== '') {
            return $slidesText;
        }

        return trim((string) $lesson->reading_content);
    }

    /**
     * Validate the shape OpenAI is asked to return, failing loudly rather
     * than saving anything malformed.
     *
     * @param  array<string, mixed>  $raw
     * @return list<array{question_text: string, options: list<string>, correct_answer: string, explanation: string}>
     */
    private function validateQuestions(array $raw): array
    {
        $questions = $raw['questions'] ?? null;

        if (! is_array($questions) || $questions === []) {
            throw new InvalidArgumentException('OpenAI response did not include a non-empty "questions" array.');
        }

        $validated = [];

        foreach ($questions as $index => $question) {
            if (! is_array($question)) {
                throw new InvalidArgumentException("Question at index {$index} is not an object.");
            }

            $questionText = $question['question_text'] ?? null;
            $options = $question['options'] ?? null;
            $correctAnswer = $question['correct_answer'] ?? null;
            $explanation = $question['explanation'] ?? null;

            if (! is_string($questionText) || trim($questionText) === '') {
                throw new InvalidArgumentException("Question at index {$index} is missing \"question_text\".");
            }

            $optionsAreValidStrings = is_array($options) && array_is_list($options)
                && count(array_filter($options, fn ($option) => is_string($option) && trim($option) !== '')) === count($options);

            if (! is_array($options) || count($options) !== 4 || ! $optionsAreValidStrings) {
                throw new InvalidArgumentException("Question at index {$index} does not have exactly 4 valid options.");
            }

            if (! is_string($correctAnswer) || ! in_array($correctAnswer, $options, true)) {
                throw new InvalidArgumentException("Question at index {$index} has a \"correct_answer\" that isn't one of its options.");
            }

            if (! is_string($explanation) || trim($explanation) === '') {
                throw new InvalidArgumentException("Question at index {$index} is missing \"explanation\".");
            }

            $validated[] = [
                'question_text' => $questionText,
                'options' => array_values($options),
                'correct_answer' => $correctAnswer,
                'explanation' => $explanation,
            ];
        }

        return $validated;
    }
}
