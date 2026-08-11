<?php

namespace App\Filament\Resources\SupportTickets;

use App\Enums\MessageType;
use App\Enums\SupportTicketStatus;
use App\Models\SupportTicket;
use App\Notifications\SupportTicketReplied;
use App\Support\RichText;

/**
 * Shared side effects for admin ticket actions, reused by both the table's
 * row actions and the ViewRecord page's header actions.
 */
class SupportTicketActions
{
    public static function reply(SupportTicket $ticket, string $body): void
    {
        $ticket->channel->messages()->create([
            'user_id' => auth()->id(),
            'type' => MessageType::Text,
            'body' => RichText::sanitize($body),
        ]);

        $ticket->teacher->notify(new SupportTicketReplied($ticket, auth()->user()));
    }

    public static function toggleStatus(SupportTicket $ticket): void
    {
        $ticket->update($ticket->status === SupportTicketStatus::Open
            ? ['status' => SupportTicketStatus::Resolved, 'resolved_by' => auth()->id(), 'resolved_at' => now()]
            : ['status' => SupportTicketStatus::Open, 'resolved_by' => null, 'resolved_at' => null]);
    }
}
