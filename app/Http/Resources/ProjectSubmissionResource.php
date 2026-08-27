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
            // Not $media->getUrl() — the 'bunny' disk has no public CDN
            // hostname configured, so that never resolves to a working
            // URL (same reason LessonTutorGuide's images are served via
            // their own controller route instead of linked directly).
            'screenshots' => $this->getMedia('screenshots')->map(fn ($media) => [
                'id' => $media->id,
                'url' => route('project-submissions.media', ['projectSubmission' => $this->id, 'media' => $media->id]),
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
