<?php

use App\Http\Controllers\LessonSlideshowPlaybackController;
use Illuminate\Support\Facades\Route;

// Unlike lesson-videos.php, this always requires auth — LessonSlideshowPolicy
// has no guest path at all, so there's no reason to leave the route open.
Route::get('lessons/{lesson}/slideshow/playback-token', LessonSlideshowPlaybackController::class)
    ->middleware(['auth', 'verified'])
    ->name('lessons.slideshow.playback-token');
