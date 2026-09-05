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
    public function generateQuestions(string $sourceText, string $gradeBand, int $questionCount): array
    {
        $response = Http::withToken(config('services.openai.key'))
            ->post(self::API_URL, [
                'model' => self::MODEL,
                'temperature' => 0.4,
                'response_format' => ['type' => 'json_object'],
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'You write multiple-choice quiz questions for an online tutoring platform. '
                            .'Respond with strict JSON only, matching the requested shape exactly — no prose, '
                            .'no markdown code fences.',
                    ],
                    [
                        'role' => 'user',
                        'content' => $this->buildPrompt($sourceText, $gradeBand, $questionCount),
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

    private function buildPrompt(string $sourceText, string $gradeBand, int $questionCount): string
    {
        return <<<PROMPT
            Generate {$questionCount} multiple-choice quiz questions based on the lesson slide content below.
            The audience is students in {$gradeBand}, so match question difficulty and language to that grade band.

            Requirements for each question:
            - Exactly 4 answer options.
            - Exactly one correct answer, which must be one of the 4 options verbatim.
            - A short explanation of why the correct answer is correct.

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
}
