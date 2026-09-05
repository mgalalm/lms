<?php

use App\Http\Controllers\Teacher\LmsController;
use App\Http\Controllers\Teacher\LmsLessonCompletionController;
use App\Http\Controllers\Teacher\LmsLessonQuizAttemptController;
use App\Http\Controllers\Teacher\LmsQuizAttemptController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'role:teacher'])->prefix('lms')->name('lms.')->group(function () {
    Route::get('/', [LmsController::class, 'index'])->name('index');
    Route::get('/{course}', [LmsController::class, 'show'])->name('show');
    Route::post('/{course}/lessons/{lesson}/complete', [LmsLessonCompletionController::class, 'store'])->name('lessons.complete');
    Route::post('/{course}/lessons/{lesson}/quiz', [LmsLessonQuizAttemptController::class, 'store'])->name('lessons.quiz.submit');
    Route::post('/{course}/quiz', [LmsQuizAttemptController::class, 'store'])->name('quiz.submit');
});
