<?php

namespace App\Jobs;

use App\Models\ClassSession;
use App\Services\Zoom\ZoomClient;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class CreateZoomMeetingJob implements ShouldQueue
{
    use Queueable;

    private const DEFAULT_DURATION_MINUTES = 60;

    /**
     * Create a new job instance.
     */
    public function __construct(public ClassSession $classSession)
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(ZoomClient $zoomClient): void
    {
        $zoomUserId = $this->classSession->teacher->teacherProfile?->zoom_user_id;

        if (! $zoomUserId) {
            Log::warning('Skipped Zoom meeting creation: teacher has no zoom_user_id.', [
                'class_session_id' => $this->classSession->id,
                'teacher_id' => $this->classSession->teacher_id,
            ]);

            return;
        }

        $meeting = $zoomClient->createMeeting(
            zoomUserId: $zoomUserId,
            topic: $this->classSession->lesson->title,
            startTime: $this->classSession->scheduled_at,
            durationMinutes: self::DEFAULT_DURATION_MINUTES,
        );

        $this->classSession->update([
            'zoom_meeting_id' => $meeting['meeting_id'],
            'zoom_join_url' => $meeting['join_url'],
            'zoom_start_url' => $meeting['start_url'],
            'zoom_password' => $meeting['password'],
        ]);
    }
}
