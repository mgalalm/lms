<?php

namespace App\Notifications;

use App\Filament\Resources\StudentProfiles\StudentProfileResource;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class StudentRegistered extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public User $student) {}

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
            ->subject('New student registered: '.$this->student->name)
            ->greeting('Hi '.$notifiable->name.',')
            ->line($this->student->name.' ('.$this->student->email.') just created a student account.')
            ->action('View Student Profile', StudentProfileResource::getUrl('edit', ['record' => $this->student->studentProfile]));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'student_id' => $this->student->id,
            'title' => 'New student registered',
            'message' => $this->student->name.' ('.$this->student->email.')',
            'url' => StudentProfileResource::getUrl('edit', ['record' => $this->student->studentProfile]),
        ];
    }
}
