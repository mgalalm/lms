<?php

namespace App\Http\Controllers;

use App\Enums\LessonVideoStatus;
use App\Models\LessonVideo;
use App\Services\Bunny\BunnyStreamClient;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class BunnyWebhookController extends Controller
{
    /**
     * Bunny's numeric VideoStatus code for "encoding finished successfully".
     * Per Bunny's documented VideoStatus enum — reconfirm against their
     * current docs before relying on this in production.
     */
    private const FINISHED_STATUS = 4;

    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request, BunnyStreamClient $bunny): Response
    {
        if ((int) $request->input('Status') !== self::FINISHED_STATUS) {
            return response()->noContent();
        }

        $lessonVideo = LessonVideo::where('bunny_video_id', $request->input('VideoGuid'))->first();

        if (! $lessonVideo) {
            return response()->noContent();
        }

        $metadata = $bunny->getVideo($lessonVideo->bunny_video_id);

        $lessonVideo->update([
            'duration_seconds' => $metadata['duration_seconds'],
            'thumbnail_url' => $metadata['thumbnail_url'],
            'status' => LessonVideoStatus::Draft,
        ]);

        return response()->noContent();
    }
}
