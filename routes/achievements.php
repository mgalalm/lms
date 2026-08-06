<?php

use App\Http\Controllers\Student\AchievementsController;
use Illuminate\Support\Facades\Route;

Route::get('achievements', AchievementsController::class)
    ->middleware(['auth', 'verified', 'role:student'])
    ->name('achievements.index');
