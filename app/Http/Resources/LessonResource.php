<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LessonResource extends JsonResource
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
            'module' => $this->whenLoaded('module', fn () => [
                'id' => $this->module->id,
                'title' => $this->module->title,
                'course' => $this->module->relationLoaded('course') ? [
                    'id' => $this->module->course->id,
                    'grade_band' => $this->module->course->grade_band,
                ] : null,
            ]),
        ];
    }
}
