<?php

use App\Http\Controllers\Teacher\LessonSlideshowViewedController;
use Illuminate\Support\Facades\Route;

Route::post('lessons/{lesson}/slideshow/viewed', LessonSlideshowViewedController::class)
    ->middleware(['auth', 'verified'])
    ->name('lessons.slideshow.viewed');
