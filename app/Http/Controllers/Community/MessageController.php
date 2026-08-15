<?php

namespace App\Http\Controllers\Community;

use App\Http\Controllers\Controller;
use App\Http\Requests\Community\StoreMessageRequest;
use App\Models\Channel;
use App\Support\RichText;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;

class MessageController extends Controller
{
    /**
     * Post a new message (or task/reminder card) to a channel. The message
     * is created hidden from other participants until it clears automated
     * moderation (dispatched by MessageObserver) — see Message::
     * approveModeration()/flagModeration() for what happens next.
     */
    public function store(StoreMessageRequest $request, Channel $channel): RedirectResponse
    {
        $validated = $request->validated();

        $attachments = collect($request->file('attachments', []))->map(function ($file) {
            $path = $file->store('chat-attachments', 'public');

            return [
                'path' => $path,
                'url' => Storage::disk('public')->url($path),
                'filename' => $file->getClientOriginalName(),
                'mime' => $file->getMimeType(),
                'size' => $file->getSize(),
            ];
        })->all();

        $channel->messages()->create([
            'user_id' => $request->user()->id,
            'type' => $validated['type'],
            'body' => RichText::sanitize($validated['body'] ?? null),
            'meta' => $validated['meta'] ?? null,
            'attachments' => $attachments ?: null,
        ]);

        return back();
    }
}
