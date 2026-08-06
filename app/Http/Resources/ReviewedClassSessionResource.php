<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Shapes a ClassSession (with its `review`, `lesson`, and `student` eager
 * loaded) into a row for the Insights page's Reviewed Sessions list.
 */
class ReviewedClassSessionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'lesson_title' => $this->lesson?->title,
            'scheduled_at' => $this->scheduled_at->toIso8601String(),
            'student' => $this->whenLoaded('student', fn () => [
                'id' => $this->student->id,
                'name' => $this->student->name,
                'code' => $this->student->studentProfile?->code,
            ]),
            'flag_severity' => $this->review->flag_severity->value,
            'star_rating' => $this->review->star_rating,
            'total_score' => $this->review->averageScore(),
            'notes' => $this->review->notes,
        ];
    }
}
