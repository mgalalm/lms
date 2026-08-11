<?php

namespace App\Notifications;

use App\Filament\Resources\SupportTickets\SupportTicketResource;
use App\Models\SupportTicket;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SupportTicketOpened extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public SupportTicket $ticket) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('New support ticket: '.$this->ticket->code.' — '.$this->ticket->subject)
            ->greeting('Hi '.$notifiable->name.',')
            ->line($this->ticket->teacher->name.' opened a new support ticket.')
            ->line('Subject: '.$this->ticket->subject)
            ->line($this->ticket->description)
            ->action('View Ticket', SupportTicketResource::getUrl('view', ['record' => $this->ticket]));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'support_ticket_id' => $this->ticket->id,
            'title' => 'New support ticket: '.$this->ticket->code,
            'message' => $this->ticket->teacher->name.' · '.$this->ticket->subject,
            'url' => SupportTicketResource::getUrl('view', ['record' => $this->ticket]),
        ];
    }
}
