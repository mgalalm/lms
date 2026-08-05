<?php

namespace App\Http\Controllers\Community;

use App\Events\MessageSent;
use App\Http\Controllers\Controller;
use App\Http\Requests\Community\StoreMessageRequest;
use App\Models\Channel;
use App\Support\RichText;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;

class MessageController extends Controller
{
    /**
     * Post a new message (or task/reminder card) to a channel.
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

        $message = $channel->messages()->create([
            'user_id' => $request->user()->id,
            'type' => $validated['type'],
            'body' => RichText::sanitize($validated['body'] ?? null),
            'meta' => $validated['meta'] ?? null,
            'attachments' => $attachments ?: null,
        ]);

        $message->load(['user.teacherProfile', 'user.studentProfile']);

        broadcast(new MessageSent($message))->toOthers();

        return back();
    }
}
