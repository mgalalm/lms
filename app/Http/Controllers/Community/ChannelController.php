<?php

namespace App\Http\Controllers\Community;

use App\Enums\ChannelType;
use App\Http\Controllers\Controller;
use App\Http\Resources\ChannelResource;
use App\Http\Resources\MessageResource;
use App\Models\Channel;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class ChannelController extends Controller
{
    /**
     * Show the channel list with no conversation selected.
     */
    public function index(Request $request): Response
    {
        return Inertia::render('community/Index', [
            'channels' => $this->channelsFor($request->user()),
            'selectedChannel' => null,
            'messages' => [],
        ]);
    }

    /**
     * Show the channel list with the given conversation open.
     */
    public function show(Request $request, Channel $channel): Response
    {
        Gate::authorize('view', $channel);

        $user = $request->user();

        $user->channels()->updateExistingPivot($channel->id, [
            'last_read_at' => now(),
        ]);

        $channel->load([
            'users.teacherProfile',
            'users.studentProfile',
            'latestMessage' => fn ($query) => $query->visibleTo($user),
        ]);

        $messages = $channel->messages()
            ->visibleTo($user)
            ->with(['user.teacherProfile', 'user.studentProfile'])
            ->latest()
            ->limit(50)
            ->get()
            ->reverse()
            ->values();

        return Inertia::render('community/Index', [
            'channels' => $this->channelsFor($user),
            'selectedChannel' => new ChannelResource($channel),
            'messages' => MessageResource::collection($messages),
        ]);
    }

    /**
     * Mark a channel as read up to now (called when new live messages arrive).
     */
    public function markRead(Request $request, Channel $channel): RedirectResponse
    {
        Gate::authorize('view', $channel);

        $request->user()->channels()->updateExistingPivot($channel->id, [
            'last_read_at' => now(),
        ]);

        return back();
    }

    /**
     * Build the sidebar channel list for the given user, with unread counts.
     */
    private function channelsFor(User $user): Collection
    {
        $channels = $user->channels()
            ->where('type', '!=', ChannelType::Support)
            ->with([
                'users.teacherProfile',
                'users.studentProfile',
                'latestMessage' => fn ($query) => $query->visibleTo($user),
            ])
            ->get();

        $channels->each(function (Channel $channel) use ($user) {
            $lastReadAt = $channel->pivot->last_read_at;

            $channel->unread_count = $channel->messages()
                ->approved()
                ->where('user_id', '!=', $user->id)
                ->when($lastReadAt, fn ($query) => $query->where('created_at', '>', $lastReadAt))
                ->count();
        });

        return $channels
            ->sortByDesc(fn (Channel $channel) => $channel->latestMessage?->created_at ?? $channel->created_at)
            ->values()
            ->map(fn (Channel $channel) => new ChannelResource($channel));
    }
}
