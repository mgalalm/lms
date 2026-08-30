<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SupportTicketResource extends JsonResource
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
            'code' => $this->code,
            'subject' => $this->subject,
            'description' => $this->description,
            'status' => $this->status->value,
            'channel_id' => $this->channel_id,
            'created_at' => $this->created_at->toIso8601String(),
            'resolved_at' => $this->resolved_at?->toIso8601String(),
            'rating' => $this->rating,
            'comment' => $this->comment,
            'class_session' => $this->whenLoaded('classSession', fn () => $this->classSession ? [
                'id' => $this->classSession->id,
                'lesson_title' => $this->classSession->lesson?->title,
            ] : null),
        ];
    }
}
