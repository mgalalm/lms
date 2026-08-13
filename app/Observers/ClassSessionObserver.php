<?php

namespace App\Observers;

use App\Enums\SessionCreditReason;
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

        $classSession->student->sessionCreditLedger()->create([
            'credits' => -1,
            'reason' => SessionCreditReason::SessionUsed,
            'class_session_id' => $classSession->id,
        ]);

        Channel::findOrCreateDirect($classSession->teacher, $classSession->student);

        $classSession->teacher->notify(new ClassSessionScheduled($classSession, 'teacher'));
        $classSession->student->notify(new ClassSessionScheduled($classSession, 'student'));
    }
}
