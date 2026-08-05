<?php

namespace App\Http\Resources;

use App\Enums\ChannelType;
use App\Enums\MessageType;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

class ChannelResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $viewerId = $request->user()?->id;
        $otherUser = $this->type === ChannelType::Direct
            ? $this->users->firstWhere('id', '!=', $viewerId)
            : null;

        return [
            'id' => $this->id,
            'type' => $this->type->value,
            'name' => $this->name ?? $otherUser?->name,
            'code' => $otherUser ? ($otherUser->teacherProfile?->code ?? $otherUser->studentProfile?->code) : null,
            'member_count' => $this->users->count(),
            'last_message' => $this->latestMessage ? [
                'preview' => $this->latestMessage->type === MessageType::TaskCard
                    ? ($this->latestMessage->meta['title'] ?? 'Task')
                    : Str::limit(strip_tags((string) $this->latestMessage->body), 60),
                'created_at' => $this->latestMessage->created_at->toIso8601String(),
            ] : null,
            'unread_count' => $this->unread_count ?? 0,
        ];
    }
}
