<?php

namespace App\Http\Controllers;

use App\Models\Lesson;
use App\Services\Bunny\BunnyStreamClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class LessonVideoPlaybackController extends Controller
{
    /**
     * Mint a short-expiry, token-signed Bunny playback URL for this
     * lesson's teaching video. Generated fresh per request — never expose
     * a raw/permanent video URL to the client.
     */
    public function __invoke(Request $request, Lesson $lesson, BunnyStreamClient $bunny): JsonResponse
    {
        $video = $lesson->video;

        abort_if(! $video || ! $video->bunny_video_id, 404);

        Gate::authorize('view', $video);

        return response()->json([
            'url' => $bunny->signedPlaybackUrl($video->bunny_video_id),
        ]);
    }
}
