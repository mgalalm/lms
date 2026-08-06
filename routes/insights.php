<?php

use App\Http\Controllers\Teacher\InsightsController;
use Illuminate\Support\Facades\Route;

Route::get('insights', InsightsController::class)
    ->middleware(['auth', 'verified', 'role:teacher'])
    ->name('insights.index');
