<?php

namespace App\Notifications;

use App\Models\ClassSession;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;

class ParentPinged extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public ClassSession $classSession,
        public string $message,
    ) {}

    /**
     * Parents have no account in the app — this is always sent via an
     * on-demand notification route (Notification::route('mail', ...)),
     * never to a Notifiable model, so mail is the only usable channel.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $student = $this->classSession->student;
        $teacher = $this->classSession->teacher;
        $parentContact = $student->parentContact;
        $scheduledAt = $this->classSession->scheduled_at->copy()->setTimezone($student->resolvedTimezone());

        $mail = (new MailMessage)
            ->subject('A note from '.$teacher->name.' about '.$student->name)
            ->greeting('Hi'.($parentContact?->name ? ' '.$parentContact->name : '').',')
            ->line($teacher->name.', '.$student->name."'s tutor, wanted to reach out about a session.")
            ->line($this->lessonPath())
            ->line('Scheduled for: '.$scheduledAt->format('l, M j, Y \a\t g:i A T'))
            ->line($this->message)
            ->line('If you have any questions, please reply to this email or contact us via the support channel in the app.');

        if ($parentContact) {
            $mail->line('')->action('Manage your notification preferences', URL::temporarySignedRoute(
                'parents.preferences.show',
                now()->addDays(7),
                ['parentContact' => $parentContact->id],
            ));
        }

        return $mail;
    }

    private function lessonPath(): string
    {
        $lesson = $this->classSession->lesson;

        return collect([$lesson->module?->course?->name, $lesson->module?->title, $lesson->title])
            ->filter()
            ->join(' / ');
    }
}
