<?php

namespace App\Services\Bunny;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

/**
 * Thin wrapper around Bunny Stream's Video Library API and its
 * token-authenticated embed playback URLs.
 *
 * Endpoints/params follow Bunny's publicly documented Video Library API
 * and Token Authentication scheme as of this writing — reconfirm against
 * https://docs.bunny.net/reference/video_video_create and
 * https://docs.bunny.net/docs/stream-embed-view-token-authentication
 * before relying on this in production, since these were not fetched live.
 */
class BunnyStreamClient
{
    private const API_BASE_URL = 'https://video.bunnycdn.com/library';

    private const EMBED_BASE_URL = 'https://iframe.mediadelivery.net/embed';

    /**
     * Create a video entry in the library, returning its GUID. The actual
     * video file is pushed separately via uploadVideo().
     */
    public function createVideo(string $title): string
    {
        $response = $this->request()
            ->post(self::API_BASE_URL.'/'.$this->libraryId().'/videos', [
                'title' => $title,
            ])
            ->throw();

        return $response->json('guid');
    }

    /**
     * Push the raw video file contents to Bunny for encoding. Bunny starts
     * transcoding asynchronously and calls our webhook when it's done.
     */
    public function uploadVideo(string $videoId, string $contents): void
    {
        $this->request()
            ->withBody($contents, 'application/octet-stream')
            ->put(self::API_BASE_URL.'/'.$this->libraryId().'/videos/'.$videoId)
            ->throw();
    }

    /**
     * Delete a video from the library (e.g. when a lesson's video is
     * replaced with a new upload).
     */
    public function deleteVideo(string $videoId): void
    {
        $this->request()
            ->delete(self::API_BASE_URL.'/'.$this->libraryId().'/videos/'.$videoId)
            ->throw();
    }

    /**
     * Fetch encoding metadata once processing finishes (duration, thumbnail).
     *
     * @return array{duration_seconds: ?int, thumbnail_url: ?string}
     */
    public function getVideo(string $videoId): array
    {
        $response = $this->request()
            ->get(self::API_BASE_URL.'/'.$this->libraryId().'/videos/'.$videoId)
            ->throw();

        $thumbnailFileName = $response->json('thumbnailFileName');

        return [
            'duration_seconds' => $response->json('length'),
            'thumbnail_url' => $thumbnailFileName
                ? self::API_BASE_URL.'/'.$this->libraryId().'/videos/'.$videoId.'/'.$thumbnailFileName
                : null,
        ];
    }

    /**
     * Build a short-expiry, token-signed embed URL for playback. Never
     * hand out a raw/permanent Bunny URL — always mint one of these
     * on-demand, per request.
     */
    public function signedPlaybackUrl(string $videoId, int $ttlSeconds = 300): string
    {
        $expires = now()->addSeconds($ttlSeconds)->timestamp;
        $token = hash('sha256', $this->apiKey().$videoId.$expires);

        return self::EMBED_BASE_URL."/{$this->libraryId()}/{$videoId}?token={$token}&expires={$expires}";
    }

    private function request(): PendingRequest
    {
        return Http::withHeaders([
            'AccessKey' => $this->apiKey(),
            'accept' => 'application/json',
        ]);
    }

    private function apiKey(): string
    {
        return (string) config('services.bunny_stream.api_key');
    }

    private function libraryId(): string
    {
        return (string) config('services.bunny_stream.library_id');
    }
}
