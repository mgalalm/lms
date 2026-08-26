<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProjectSubmissionResource extends JsonResource
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
            'description' => $this->description,
            'live_url' => $this->live_url,
            'status' => $this->status->value,
            'submitted_at' => $this->submitted_at->toIso8601String(),
            'screenshots' => $this->getMedia('screenshots')->map(fn ($media) => [
                'id' => $media->id,
                'url' => $media->getUrl(),
            ])->values(),
            'feedback' => $this->whenLoaded('feedback', fn () => $this->feedback ? new ProjectFeedbackResource($this->feedback) : null),
            'lesson_project' => $this->whenLoaded('lessonProject', fn () => new LessonProjectResource($this->lessonProject)),
            'student' => $this->whenLoaded('student', fn () => [
                'id' => $this->student->id,
                'name' => $this->student->name,
                'code' => $this->student->studentProfile?->code,
            ]),
        ];
    }
}
