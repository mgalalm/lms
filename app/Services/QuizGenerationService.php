<?php

namespace App\Services;

use App\Enums\LessonQuizStatus;
use App\Models\Lesson;
use App\Models\LessonQuiz;
use App\Services\OpenAI\QuizGenerationClient;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

class QuizGenerationService
{
    public function __construct(private readonly QuizGenerationClient $client) {}

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

        $gradeBand = $lesson->module->course->grade_band;

        $raw = $this->client->generateQuestions($sourceText, $gradeBand, $questionCount);
        $questions = $this->validateQuestions($raw);

        return DB::transaction(function () use ($lesson, $sourceText, $questions) {
            $quiz = $lesson->quiz()->updateOrCreate(
                ['locale' => 'en'],
                [
                    'status' => LessonQuizStatus::Draft,
                    'generated_at' => now(),
                    'source_text_hash' => hash('sha256', $sourceText),
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
