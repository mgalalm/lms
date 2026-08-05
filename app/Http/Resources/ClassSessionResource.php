<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ClassSessionResource extends JsonResource
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
            'type' => $this->type->value,
            'status' => $this->status->value,
            'scheduled_at' => $this->scheduled_at->toIso8601String(),
            'join_url' => $this->when($request->user()?->id === $this->student_id, $this->zoom_join_url),
            'start_url' => $this->when($request->user()?->id === $this->teacher_id, $this->zoom_start_url),
            'lesson' => new LessonResource($this->whenLoaded('lesson')),
            'teacher' => $this->whenLoaded('teacher', fn () => [
                'id' => $this->teacher->id,
                'name' => $this->teacher->name,
                'code' => $this->teacher->teacherProfile?->code,
            ]),
            'student' => $this->whenLoaded('student', fn () => [
                'id' => $this->student->id,
                'name' => $this->student->name,
                'code' => $this->student->studentProfile?->code,
                'language' => $this->student->studentProfile?->language,
            ]),
        ];
    }
}
