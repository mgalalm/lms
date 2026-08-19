<?php

namespace App\Http\Controllers;

use App\Enums\LessonVideoStatus;
use App\Models\LessonVideo;
use App\Services\Bunny\BunnyStreamClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BunnyWebhookController extends Controller
{
    /**
     * Bunny's numeric VideoStatus code for "encoding finished successfully".
     * Per Bunny's documented VideoStatus enum — reconfirm against their
     * current docs before relying on this in production.
     */
    private const FINISHED_STATUS = 4;

    /**
     * Handle the incoming request. Always returns 200 so Bunny doesn't
     * retry unnecessarily — a webhook for a status we don't act on, an
     * unrecognized video, or an already-processed video is a no-op, not
     * an error.
     */
    public function __invoke(Request $request, BunnyStreamClient $bunny): JsonResponse
    {
        if ((int) $request->input('VideoLibraryId') !== (int) config('services.bunny_stream.library_id')) {
            return $this->acknowledge();
        }

        if ((int) $request->input('Status') !== self::FINISHED_STATUS) {
            return $this->acknowledge();
        }

        $lessonVideo = LessonVideo::where('bunny_video_id', $request->input('VideoGuid'))->first();

        if (! $lessonVideo) {
            return $this->acknowledge();
        }

        // Idempotency: only Processing -> Draft transitions here. A
        // retried webhook (or one arriving after the video was already
        // published) must not re-fetch metadata or clobber a later state.
        if ($lessonVideo->status !== LessonVideoStatus::Processing) {
            return $this->acknowledge();
        }

        $metadata = $bunny->getVideo($lessonVideo->bunny_video_id);

        $lessonVideo->update([
            'duration_seconds' => $metadata['duration_seconds'],
            'thumbnail_url' => $metadata['thumbnail_url'],
            'status' => LessonVideoStatus::Draft,
        ]);

        return $this->acknowledge();
    }

    private function acknowledge(): JsonResponse
    {
        return response()->json(['status' => 'ok']);
    }
}
