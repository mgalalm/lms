<?php

use App\Http\Controllers\Student\ProjectSubmissionController;
use App\Http\Controllers\Teacher\ProjectsController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'role:student'])->group(function () {
    Route::post('lesson-projects/{lessonProject}/submissions', [ProjectSubmissionController::class, 'store'])
        ->name('lesson-projects.submissions.store');
});

Route::middleware(['auth', 'verified', 'role:teacher'])->prefix('teaching')->name('teaching.')->group(function () {
    Route::get('projects', [ProjectsController::class, 'index'])->name('projects.index');
    Route::get('projects/{projectSubmission}', [ProjectsController::class, 'show'])->name('projects.show');
    Route::post('projects/{projectSubmission}/feedback', [ProjectsController::class, 'feedback'])->name('projects.feedback');
});
