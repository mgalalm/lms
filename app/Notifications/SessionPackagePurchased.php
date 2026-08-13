<?php

namespace App\Notifications;

use App\Models\SessionPackagePurchase;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SessionPackagePurchased extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public SessionPackagePurchase $purchase,
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
        return (new MailMessage)
            ->subject('Payment received: '.$this->purchase->sessionPackage->name)
            ->greeting('Hi '.$notifiable->name.',')
            ->line('Your payment for "'.$this->purchase->sessionPackage->name.'" has been received.')
            ->line($this->purchase->session_count.' session credits have been added to your account.')
            ->action('View Session Packages', route('session-packages.index'));
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'session_package_purchase_id' => $this->purchase->id,
            'title' => 'Session credits added',
            'message' => $this->purchase->session_count.' credits added from "'.$this->purchase->sessionPackage->name.'"',
            'url' => route('session-packages.index'),
        ];
    }
}
