<?php

namespace App\Notifications;

use App\Models\Message;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MessageFlaggedForReview extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Message $message,
    ) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $categories = implode(', ', $this->message->moderation_flags ?? []) ?: 'unspecified';

        return (new MailMessage)
            ->subject('A message was flagged for review')
            ->greeting('Hi '.$notifiable->name.',')
            ->line('A message from '.$this->message->user->name.' was automatically hidden pending review.')
            ->line('Flagged categories: '.$categories)
            ->action('Review Message', route('filament.admin.resources.flagged-messages.view', $this->message))
            ->line('The message stays hidden from other participants until an admin approves or removes it.');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'message_id' => $this->message->id,
            'title' => 'Message flagged for review',
            'message' => $this->message->user->name.' · '.implode(', ', $this->message->moderation_flags ?? []),
            'url' => route('filament.admin.resources.flagged-messages.view', $this->message),
        ];
    }
}
