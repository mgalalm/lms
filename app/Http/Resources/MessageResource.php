<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MessageResource extends JsonResource
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
            'channel_id' => $this->channel_id,
            'type' => $this->type->value,
            'body' => $this->body,
            'meta' => $this->meta,
            'attachments' => $this->attachments,
            'created_at' => $this->created_at->toIso8601String(),
            'user' => [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'role' => $this->user->getRoleNames()->first(),
                'code' => $this->user->teacherProfile?->code ?? $this->user->studentProfile?->code,
            ],
        ];
    }
}
