<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Sent when a parent uses "Request a preferences link". Parents have no
 * account in the app, so this is always sent via an on-demand notification
 * route, never to a Notifiable model.
 */
class ParentPreferenceLinkRequested extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public string $link) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Manage your notification preferences')
            ->greeting('Hi,')
            ->line('Here\'s the link you requested to manage your notification preferences:')
            ->action('Manage preferences', $this->link)
            ->line('This link expires in 7 days. If you didn\'t request this, you can ignore this email.');
    }
}
