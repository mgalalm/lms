<?php

namespace App\Observers;

use App\Enums\SessionType;
use App\Jobs\CreateZoomMeetingJob;
use App\Models\Channel;
use App\Models\ClassSession;

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
    }
}
