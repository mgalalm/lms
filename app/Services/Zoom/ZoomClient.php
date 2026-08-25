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
