<?php

namespace App\Notifications;

use App\Models\SupportTicket;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SupportTicketReplied extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public SupportTicket $ticket,
        public User $repliedBy,
    ) {}

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
            ->subject('New reply on ticket '.$this->ticket->code.': '.$this->ticket->subject)
            ->greeting('Hi '.$notifiable->name.',')
            ->line($this->repliedBy->name.' replied to your support ticket.')
            ->line('Subject: '.$this->ticket->subject)
            ->action('View Ticket', route('teaching.support-tickets.show', $this->ticket));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'support_ticket_id' => $this->ticket->id,
            'title' => 'New reply on ticket '.$this->ticket->code,
            'message' => $this->repliedBy->name.' · '.$this->ticket->subject,
            'url' => route('teaching.support-tickets.show', $this->ticket),
        ];
    }
}
