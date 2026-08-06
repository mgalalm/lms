<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/**
 * Shapes a Course (with `lessons_count`, `completed_lessons_count`, and
 * `progress_status` set on the instance by the controller) into an LMS
 * course-library card for the teacher dashboard.
 */
class LmsCourseCardResource extends JsonResource
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
            'title' => $this->title,
            'grade_band' => $this->grade_band,
            'thumbnail_url' => $this->thumbnail_path ? Storage::disk('public')->url($this->thumbnail_path) : null,
            'lessons_count' => $this->lessons_count,
            'completed_lessons_count' => $this->completed_lessons_count,
            'progress_percentage' => $this->lessons_count > 0
                ? (int) round($this->completed_lessons_count / $this->lessons_count * 100)
                : 0,
            'status' => $this->progress_status->value,
            'status_label' => $this->progress_status->label(),
        ];
    }
}
