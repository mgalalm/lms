<?php

use App\Http\Controllers\ClassSessionRecapController;
use Illuminate\Support\Facades\Route;

Route::get('sessions/{classSession}/recap', ClassSessionRecapController::class)
    ->middleware(['auth', 'verified'])
    ->name('sessions.recap');
