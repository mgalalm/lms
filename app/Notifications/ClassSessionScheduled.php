<?php

namespace App\Notifications;

use App\Models\ClassSession;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ClassSessionScheduled extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * @param  'teacher'|'student'  $viewerRole  Which side of the session this notification is for.
     */
    public function __construct(
        public ClassSession $classSession,
        public string $viewerRole,
    ) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database', 'broadcast'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $otherParty = $this->otherParty();
        $scheduledAt = $this->classSession->scheduled_at->copy()->setTimezone($notifiable->resolvedTimezone());

        return (new MailMessage)
            ->subject('New session scheduled: '.$this->lessonPath())
            ->greeting('Hi '.$notifiable->name.',')
            ->line('A new session has been scheduled with '.$otherParty->name.'.')
            ->line($this->lessonPath())
            ->line('Scheduled for: '.$scheduledAt->format('l, M j, Y \a\t g:i A T'))
            ->action('View Session', $this->url());
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'class_session_id' => $this->classSession->id,
            'title' => 'New session scheduled',
            'message' => $this->otherParty()->name.' · '.$this->lessonPath(),
            'scheduled_at' => $this->classSession->scheduled_at->toIso8601String(),
            'url' => $this->url(),
        ];
    }

    /**
     * Get the broadcastable representation of the notification.
     */
    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->toArray($notifiable));
    }

    /**
     * Get the type of the notification being broadcast.
     */
    public function broadcastType(): string
    {
        return 'class-session.scheduled';
    }

    private function otherParty(): User
    {
        return $this->viewerRole === 'teacher' ? $this->classSession->student : $this->classSession->teacher;
    }

    private function lessonPath(): string
    {
        $lesson = $this->classSession->lesson;

        return collect([$lesson->module?->course?->name, $lesson->module?->title, $lesson->title])
            ->filter()
            ->join(' / ');
    }

    private function url(): string
    {
        return $this->viewerRole === 'teacher' ? route('teaching.index') : route('dashboard');
    }
}
