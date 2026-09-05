<?php

use App\Http\Controllers\Teacher\LmsController;
use App\Http\Controllers\Teacher\LmsLessonCompletionController;
use App\Http\Controllers\Teacher\LmsModuleQuizAttemptController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'role:teacher'])->prefix('lms')->name('lms.')->group(function () {
    Route::get('/', [LmsController::class, 'index'])->name('index');
    Route::get('/{course}', [LmsController::class, 'show'])->name('show');
    Route::post('/{course}/lessons/{lesson}/complete', [LmsLessonCompletionController::class, 'store'])->name('lessons.complete');
    // Certification is per module: completing a module's lessons unlocks
    // its quiz, and passing that quiz is what allows being assigned to
    // teach the module's lessons.
    Route::post('/{course}/modules/{module}/quiz/start', [LmsModuleQuizAttemptController::class, 'start'])->name('modules.quiz.start');
    Route::post('/{course}/modules/{module}/quiz', [LmsModuleQuizAttemptController::class, 'store'])->name('modules.quiz.submit');
});
