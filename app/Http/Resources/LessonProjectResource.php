<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LessonProjectResource extends JsonResource
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
            'brief' => $this->brief,
            'is_required' => $this->is_required,
            'lesson' => $this->whenLoaded('lesson', fn () => new LessonResource($this->lesson)),
        ];
    }
}
