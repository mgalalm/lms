<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * The email leg of contact-point verification. Parents have no account in
 * the app, so — like ParentPinged — this is always sent via an on-demand
 * notification route (Notification::route('mail', ...)), never to a
 * Notifiable model.
 */
class ParentContactVerificationCode extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public string $code) {}

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
            ->subject('Your verification code')
            ->greeting('Hi,')
            ->line('Use this code to verify this email address as a parent/guardian contact:')
            ->line("**{$this->code}**")
            ->line('This code expires in 15 minutes. If you didn\'t request this, you can ignore this email.');
    }
}
