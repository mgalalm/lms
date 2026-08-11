<?php

namespace App\Http\Controllers\Teacher;

use App\Enums\ChannelType;
use App\Enums\MessageType;
use App\Enums\SupportTicketStatus;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Resources\MessageResource;
use App\Http\Resources\SupportTicketResource;
use App\Models\Channel;
use App\Models\ClassSession;
use App\Models\SupportTicket;
use App\Models\User;
use App\Notifications\SupportTicketOpened;
use App\Support\RichText;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Inertia\Inertia;
use Inertia\Response;

class SupportTicketController extends Controller
{
    /**
     * Open a new support ticket about a specific session.
     */
    public function store(Request $request, ClassSession $classSession): RedirectResponse
    {
        abort_unless($classSession->teacher_id === $request->user()->id, 403);

        $data = $request->validate([
            'subject' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:5000'],
        ]);

        $admins = User::role(UserRole::Admin->value)->get();

        $channel = Channel::create(['type' => ChannelType::Support]);
        $channel->users()->attach(
            $admins->pluck('id')->push($request->user()->id)->unique()
        );

        $ticket = SupportTicket::create([
            'teacher_id' => $request->user()->id,
            'class_session_id' => $classSession->id,
            'channel_id' => $channel->id,
            'subject' => $data['subject'],
            'description' => $data['description'],
        ]);

        $channel->messages()->create([
            'user_id' => $request->user()->id,
            'type' => MessageType::Text,
            'body' => RichText::sanitize($data['description']),
        ]);

        Notification::send($admins, new SupportTicketOpened($ticket));

        return redirect()->route('teaching.support-tickets.show', $ticket);
    }

    /**
     * Show a ticket's thread.
     */
    public function show(Request $request, SupportTicket $supportTicket): Response
    {
        abort_unless($supportTicket->teacher_id === $request->user()->id, 403);

        $request->user()->channels()->updateExistingPivot($supportTicket->channel_id, [
            'last_read_at' => now(),
        ]);

        $supportTicket->load(['classSession.lesson']);

        $messages = $supportTicket->channel->messages()
            ->with(['user.teacherProfile', 'user.studentProfile'])
            ->oldest()
            ->get();

        return Inertia::render('teacher/SupportTicketShow', [
            'ticket' => new SupportTicketResource($supportTicket),
            'messages' => MessageResource::collection($messages),
        ]);
    }

    /**
     * Mark a ticket as resolved.
     */
    public function resolve(Request $request, SupportTicket $supportTicket): RedirectResponse
    {
        abort_unless($supportTicket->teacher_id === $request->user()->id, 403);

        $supportTicket->update([
            'status' => SupportTicketStatus::Resolved,
            'resolved_by' => $request->user()->id,
            'resolved_at' => now(),
        ]);

        return back();
    }
}
