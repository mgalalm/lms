<?php

namespace App\Notifications;

use App\Models\ClassSession;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ParentPinged extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public ClassSession $classSession,
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
        $parentName = $student->studentProfile?->parent_name;
        $scheduledAt = $this->classSession->scheduled_at->copy()->setTimezone($student->resolvedTimezone());

        return (new MailMessage)
            ->subject('A note from '.$teacher->name.' about '.$student->name)
            ->greeting('Hi'.($parentName ? ' '.$parentName : '').',')
            ->line($teacher->name.', '.$student->name."'s tutor, wanted to reach you about an upcoming session.")
            ->line($this->lessonPath())
            ->line('Scheduled for: '.$scheduledAt->format('l, M j, Y \a\t g:i A T'))
            ->line('If you have any questions, please reply to this email or contact us via the support channel in the app.');
    }

    private function lessonPath(): string
    {
        $lesson = $this->classSession->lesson;

        return collect([$lesson->module?->course?->title, $lesson->module?->title, $lesson->title])
            ->filter()
            ->join(' / ');
    }
}
