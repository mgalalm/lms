<?php

use App\Http\Controllers\Student\LessonQuizAttemptController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'role:student'])->group(function () {
    Route::post('lessons/{lesson}/quiz', [LessonQuizAttemptController::class, 'store'])
        ->name('lessons.quiz.submit');
});
