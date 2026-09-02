<?php

namespace App\Services\Zoom;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class ZoomClient
{
    private const OAUTH_URL = 'https://zoom.us/oauth/token';

    private const API_BASE_URL = 'https://api.zoom.us/v2';

    private const TOKEN_CACHE_KEY = 'zoom:s2s-access-token';

    /**
     * Create a scheduled meeting hosted by the given Zoom user.
     *
     * @return array{meeting_id: string, join_url: string, start_url: string, password: ?string}
     */
    public function createMeeting(string $zoomUserId, string $topic, \DateTimeInterface $startTime, int $durationMinutes): array
    {
        $response = Http::withToken($this->getAccessToken())
            ->post(self::API_BASE_URL."/users/{$zoomUserId}/meetings", [
                'topic' => $topic,
                'type' => 2,
                'start_time' => $startTime->format('Y-m-d\TH:i:s\Z'),
                'duration' => $durationMinutes,
                'timezone' => 'UTC',
                'settings' => [
                    'host_video' => true,
                    'participant_video' => true,
                    'join_before_host' => false,
                    // Explicit, not left to the Zoom account's own default
                    // (commonly on) — join_before_host already guarantees
                    // the teacher is already in the meeting before a
                    // student can enter, so the waiting room would only
                    // add a manual per-student admit click with no extra
                    // safety benefit here.
                    'waiting_room' => false,
                    'auto_recording' => 'cloud',
                ],
            ])
            ->throw();

        return [
            'meeting_id' => (string) $response->json('id'),
            'join_url' => $response->json('join_url'),
            'start_url' => $response->json('start_url'),
            'password' => $response->json('password'),
        ];
    }

    /**
     * Disable the "Co-host" permission on the given Zoom user's account, so
     * a host running a meeting under it can no longer promote any
     * participant (including a student) to co-host — a co-host gets the
     * same in-meeting controls as the host. Idempotent, safe to re-run.
     */
    public function disableCoHost(string $zoomUserId): void
    {
        Http::withToken($this->getAccessToken())
            ->patch(self::API_BASE_URL."/users/{$zoomUserId}/settings", [
                'in_meeting' => ['co_host' => false],
            ])
            ->throw();
    }

    /**
     * Look up a past meeting's recap play URL directly, for backfilling
     * sessions whose recording.completed webhook was never captured (or
     * was captured before zoom_recap_url existed). Returns null if Zoom
     * has no MP4 recording for this meeting — either it was never
     * recorded, or it's aged out of Zoom's cloud retention window.
     */
    public function findRecapUrl(string $meetingId): ?string
    {
        $response = Http::withToken($this->getAccessToken())
            ->get(self::API_BASE_URL."/meetings/{$meetingId}/recordings");

        if ($response->status() === 404) {
            return null;
        }

        $response->throw();

        $videoFile = collect($response->json('recording_files', []))
            ->firstWhere('file_type', 'MP4');

        return $videoFile['play_url'] ?? null;
    }

    /**
     * Get a cached Server-to-Server OAuth access token, fetching a new one if expired.
     */
    private function getAccessToken(): string
    {
        return Cache::remember(self::TOKEN_CACHE_KEY, now()->addMinutes(55), function () {
            $accountId = config('services.zoom.account_id');
            $clientId = config('services.zoom.client_id');
            $clientSecret = config('services.zoom.client_secret');

            $response = Http::asForm()
                ->withBasicAuth($clientId, $clientSecret)
                ->post(self::OAUTH_URL, [
                    'grant_type' => 'account_credentials',
                    'account_id' => $accountId,
                ])
                ->throw();

            $token = $response->json('access_token');

            if (! $token) {
                throw new RuntimeException('Zoom OAuth token response did not include an access_token.');
            }

            return $token;
        });
    }
}
