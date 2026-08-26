<?php

namespace App\Notifications;

use App\Models\ProjectSubmission;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ProjectFeedbackSubmitted extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public ProjectSubmission $submission) {}

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
        $feedback = $this->submission->feedback;

        return (new MailMessage)
            ->subject('Your project got feedback: '.$this->submission->title)
            ->greeting('Hi '.$notifiable->name.',')
            ->line('Your teacher reviewed "'.$this->submission->title.'" and left feedback.')
            ->line('Rating: '.$feedback->rating.'/5')
            ->when($feedback->is_featured, fn (MailMessage $mail) => $mail->line('🌟 This project was marked as Featured!'))
            ->action('View Feedback', route('dashboard'));
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $feedback = $this->submission->feedback;

        return [
            'project_submission_id' => $this->submission->id,
            'title' => 'Your project got feedback',
            'message' => $this->submission->title.' · '.$feedback->rating.'/5',
            'is_featured' => $feedback->is_featured,
            'url' => route('dashboard'),
        ];
    }
}
