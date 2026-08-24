<?php

use App\Http\Controllers\Teacher\LessonTutorGuideController;
use App\Http\Controllers\Teacher\LessonTutorGuideMediaController;
use Illuminate\Support\Facades\Route;

// Not restricted to the `role:teacher` group in web.php — admins can view
// this too, and the real enforcement happens via the policy inside the
// controller, not route middleware (see LessonTutorGuidePolicy).
Route::get('lessons/{lesson}/tutor-guide', LessonTutorGuideController::class)
    ->middleware(['auth', 'verified'])
    ->name('lessons.tutor-guide');

Route::get('lesson-tutor-guides/{lessonTutorGuide}/media/{media}', LessonTutorGuideMediaController::class)
    ->middleware(['auth', 'verified'])
    ->name('lesson-tutor-guides.media');
