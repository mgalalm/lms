<?php

namespace App\Http\Controllers;

use App\Enums\SessionStatus;
use App\Jobs\DownloadZoomRecordingJob;
use App\Models\ClassSession;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ZoomWebhookController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request): Response
    {
        return match ($request->input('event')) {
            'endpoint.url_validation' => $this->validateEndpointUrl($request),
            'meeting.participant_joined' => $this->recordParticipantJoined($request),
            'meeting.ended' => $this->completeSession($request),
            'recording.completed' => $this->queueRecordingDownload($request),
            default => response()->noContent(),
        };
    }

    /**
     * Respond to Zoom's webhook endpoint verification handshake.
     */
    private function validateEndpointUrl(Request $request): Response
    {
        $plainToken = $request->input('payload.plainToken');

        return response()->json([
            'plainToken' => $plainToken,
            'encryptedToken' => hash_hmac('sha256', $plainToken, (string) config('services.zoom.webhook_secret')),
        ]);
    }

    /**
     * Record the teacher/student's first join time for on-time/late tracking.
     */
    private function recordParticipantJoined(Request $request): Response
    {
        $session = $this->findSessionByMeetingId($request->input('payload.object.id'));

        if (! $session) {
            return response()->noContent();
        }

        $participantEmail = $request->input('payload.object.participant.email');

        if ($participantEmail === $session->teacher->teacherProfile?->zoom_user_id && ! $session->teacher_joined_at) {
            $session->update(['teacher_joined_at' => now()]);
        } elseif ($participantEmail === $session->student->email && ! $session->student_joined_at) {
            $session->update(['student_joined_at' => now()]);
        }

        return response()->noContent();
    }

    /**
     * Mark the session completed once the Zoom meeting ends.
     */
    private function completeSession(Request $request): Response
    {
        $session = $this->findSessionByMeetingId($request->input('payload.object.id'));

        if ($session && $session->status !== SessionStatus::Completed) {
            $session->update([
                'status' => SessionStatus::Completed,
                'ended_at' => now(),
            ]);
        }

        return response()->noContent();
    }

    /**
     * Queue the recap video for download once Zoom finishes processing it.
     */
    private function queueRecordingDownload(Request $request): Response
    {
        $session = $this->findSessionByMeetingId($request->input('payload.object.id'));

        $videoFile = collect($request->input('payload.object.recording_files', []))
            ->firstWhere('file_type', 'MP4');

        if ($session && $videoFile && filled($videoFile['download_url'] ?? null)) {
            DownloadZoomRecordingJob::dispatch($session, $videoFile['download_url']);
        }

        return response()->noContent();
    }

    private function findSessionByMeetingId(?string $meetingId): ?ClassSession
    {
        if (! $meetingId) {
            return null;
        }

        return ClassSession::where('zoom_meeting_id', $meetingId)->first();
    }
}
