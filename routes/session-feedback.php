<?php

use App\Http\Controllers\Student\SessionFeedbackController;
use Illuminate\Support\Facades\Route;

Route::post('sessions/{classSession}/feedback', [SessionFeedbackController::class, 'store'])
    ->middleware(['auth', 'verified', 'role:student'])
    ->name('sessions.feedback.store');
