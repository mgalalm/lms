<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Student-facing shape of a published LessonQuiz — deliberately omits
 * correct_answer/explanation so answers aren't leaked to the client before
 * submission. See LessonQuizAttemptController for the post-submit reveal.
 */
class LessonQuizResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'questions' => $this->whenLoaded('questions', fn () => $this->questions->map(fn ($question) => [
                'id' => $question->id,
                'question_text' => $question->question_text,
                'type' => $question->type->value,
                'options' => $question->options,
            ])),
        ];
    }
}
