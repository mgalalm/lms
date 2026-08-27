<?php

use App\Http\Controllers\ProjectSubmissionMediaController;
use App\Http\Controllers\Student\ProjectSubmissionController;
use App\Http\Controllers\Teacher\ProjectsController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'role:student'])->group(function () {
    Route::post('lesson-projects/{lessonProject}/submissions', [ProjectSubmissionController::class, 'store'])
        ->name('lesson-projects.submissions.store');
});

// Not restricted to a role group — the student who submitted, their
// assigned teacher, or an admin can all view it; real enforcement
// happens via ProjectSubmissionPolicy::view() inside the controller.
Route::get('project-submissions/{projectSubmission}/media/{media}', ProjectSubmissionMediaController::class)
    ->middleware(['auth', 'verified'])
    ->name('project-submissions.media');

// The index route lives in web.php (TeachingController::projects) as part
// of the tabbed /teaching page — these two are unrelated to which page
// hosts the index.
Route::middleware(['auth', 'verified', 'role:teacher'])->prefix('teaching')->name('teaching.')->group(function () {
    Route::get('projects/{projectSubmission}', [ProjectsController::class, 'show'])->name('projects.show');
    Route::post('projects/{projectSubmission}/feedback', [ProjectsController::class, 'feedback'])->name('projects.feedback');
});
