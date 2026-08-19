<?php

use App\Http\Controllers\LessonVideoPlaybackController;
use Illuminate\Support\Facades\Route;

// Not wrapped in auth/verified middleware — published videos are viewable
// by anyone per LessonVideoPolicy, including guests; the policy itself
// enforces that non-published videos are teacher/admin-only.
Route::get('lessons/{lesson}/video/playback-token', LessonVideoPlaybackController::class)
    ->name('lessons.video.playback-token');
