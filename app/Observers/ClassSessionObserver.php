<?php

namespace App\Observers;

use App\Enums\SessionType;
use App\Jobs\CreateZoomMeetingJob;
use App\Models\Channel;
use App\Models\ClassSession;
use App\Notifications\ClassSessionScheduled;

class ClassSessionObserver
{
    /**
     * Handle the ClassSession "created" event.
     */
    public function created(ClassSession $classSession): void
    {
        if ($classSession->type === SessionType::Online && ! $classSession->zoom_meeting_id) {
            CreateZoomMeetingJob::dispatch($classSession);
        }

        Channel::findOrCreateDirect($classSession->teacher, $classSession->student);

        $classSession->teacher->notify(new ClassSessionScheduled($classSession, 'teacher'));
        $classSession->student->notify(new ClassSessionScheduled($classSession, 'student'));
    }
}
