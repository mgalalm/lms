<?php

namespace App\Http\Controllers;

use App\Models\Lesson;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

class LessonSlideshowPlaybackController extends Controller
{
    /**
     * Mint a short-expiry signed URL for this lesson's slideshow PDF.
     * Generated fresh per request — never expose a permanent public URL.
     */
    public function __invoke(Request $request, Lesson $lesson): JsonResponse
    {
        $slideshow = $lesson->slideshow;

        abort_if(! $slideshow || ! $slideshow->isReady(), 404);

        Gate::authorize('view', $slideshow);

        return response()->json([
            'url' => Storage::disk('bunny')->temporaryUrl($slideshow->file_path, now()->addMinutes(5)),
        ]);
    }
}
