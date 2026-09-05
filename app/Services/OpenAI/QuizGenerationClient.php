<?php

namespace App\Services\OpenAI;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class QuizGenerationClient
{
    private const API_URL = 'https://api.openai.com/v1/chat/completions';

    private const MODEL = 'gpt-4o-mini';

    /**
     * Ask OpenAI for a set of multiple-choice questions covering the given
     * source text, and return the raw decoded JSON payload. Structural
     * validation of the response happens in QuizGenerationService, not
     * here — this class is only responsible for the API round-trip.
     *
     * @return array<string, mixed>
     */
    public function generateQuestions(
        string $sourceText,
        string $gradeBand,
        int $questionCount,
        ?string $lessonTitle = null,
        ?string $moduleTitle = null,
    ): array {
        $response = Http::withToken(config('services.openai.key'))
            ->post(self::API_URL, [
                'model' => self::MODEL,
                'temperature' => 0.4,
                'response_format' => ['type' => 'json_object'],
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'You write multiple-choice quiz questions for an online tutoring platform '
                            .'used by school-age children. Every question, option and explanation you write is '
                            .'read by a minor, so age-appropriateness is a hard requirement, not a preference. '
                            .'Respond with strict JSON only, matching the requested shape exactly — no prose, '
                            .'no markdown code fences.',
                    ],
                    [
                        'role' => 'user',
                        'content' => $this->buildPrompt($sourceText, $gradeBand, $questionCount, $lessonTitle, $moduleTitle),
                    ],
                ],
            ])
            ->throw();

        $content = $response->json('choices.0.message.content');

        if (! is_string($content) || trim($content) === '') {
            throw new RuntimeException('OpenAI returned an empty quiz generation response.');
        }

        $decoded = json_decode($content, true);

        if (! is_array($decoded)) {
            throw new RuntimeException('OpenAI returned a quiz generation response that was not valid JSON.');
        }

        return $decoded;
    }

    private function buildPrompt(
        string $sourceText,
        string $gradeBand,
        int $questionCount,
        ?string $lessonTitle,
        ?string $moduleTitle,
    ): string {
        return <<<PROMPT
            Generate {$questionCount} multiple-choice quiz questions based on the lesson slide content below.

            {$this->buildLessonContext($gradeBand, $lessonTitle, $moduleTitle)}

            {$this->buildAudienceGuardrails($gradeBand)}

            Requirements for each question:
            - Exactly 4 answer options.
            - Exactly one correct answer, which must be one of the 4 options verbatim.
            - A short explanation of why the correct answer is correct.
            - Answerable purely from the slide content below. Never require outside knowledge, and never
              write a question about a topic the slide content does not actually cover.

            Respond with strict JSON only, in exactly this shape:
            {
              "questions": [
                {
                  "question_text": "string",
                  "options": ["string", "string", "string", "string"],
                  "correct_answer": "string (must match one of options exactly)",
                  "explanation": "string"
                }
              ]
            }

            Slide content (slides are separated by a blank line or "---"):
            ---
            {$sourceText}
            ---
            PROMPT;
    }

    /**
     * Title context helps the model tell the lesson's actual subject from
     * whatever happens to be most verbose in the pasted slide text. It is
     * deliberately framed as context only — titles carry ordering noise
     * ("L11: ..."), and the slide content stays the source of truth.
     */
    private function buildLessonContext(string $gradeBand, ?string $lessonTitle, ?string $moduleTitle): string
    {
        $lines = ["- Grade band: {$gradeBand}"];

        if (filled($moduleTitle)) {
            $lines[] = "- Module: {$moduleTitle}";
        }

        if (filled($lessonTitle)) {
            $lines[] = "- Lesson: {$lessonTitle}";
        }

        return "This quiz is for the following lesson:\n".implode("\n", $lines)
            ."\nUse these titles only to understand the subject matter and keep questions on-topic. "
            .'Titles may contain ordering prefixes such as "L11:" or "M2:" — ignore those. '
            .'Do not write questions about the titles themselves.';
    }

    /**
     * Hard age-appropriateness constraints. The grade band alone is too
     * abstract for the model to pitch reliably, so it's translated into an
     * explicit age range plus concrete limits on language and subject matter.
     */
    private function buildAudienceGuardrails(string $gradeBand): string
    {
        $ageRange = $this->resolveAgeRange($gradeBand);
        $audience = $ageRange === null
            ? "students in {$gradeBand}"
            : "students in {$gradeBand}, who are approximately {$ageRange} years old";

        return <<<GUARDRAILS
            The audience is {$audience}. These constraints are mandatory:
            - Pitch reading level, vocabulary and sentence length to that age. Prefer short, plain questions;
              define any technical term the slide content itself does not already introduce.
            - Match conceptual difficulty to that age — no reasoning steps or prerequisite knowledge beyond it.
            - Keep all content school-appropriate for minors: no violence, weapons, gore, sexual or romantic
              content, substances, gambling, self-harm, politics, religion, or frightening scenarios.
            - Keep worked examples wholesome and everyday (school, hobbies, animals, sport, food, family).
            - No real people, brands, or culturally specific references that a child of this age in another
              country would not recognise.
            - Never address the student by name, ask for personal information, or reference anything outside
              the lesson.
            GUARDRAILS;
    }

    /**
     * Translate a "Grade 5 - 6" style band into an approximate age range.
     * Derived arithmetically (a child is roughly grade + 5 years old) rather
     * than hardcoding the four seeded bands, so an unexpected label degrades
     * to null instead of a wrong age.
     */
    private function resolveAgeRange(string $gradeBand): ?string
    {
        if (! preg_match_all('/\d+/', $gradeBand, $matches) || $matches[0] === []) {
            return null;
        }

        $grades = array_map('intval', $matches[0]);

        return (min($grades) + 5).'-'.(max($grades) + 6);
    }
}
