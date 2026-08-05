<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Teacher\SessionsScheduleController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');

Route::get('dashboard', DashboardController::class)
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware(['auth', 'verified', 'role:teacher'])->prefix('teaching')->name('teaching.')->group(function () {
    Route::get('sessions-schedule', SessionsScheduleController::class)->name('sessions-schedule');
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
require __DIR__.'/webhooks.php';
require __DIR__.'/community.php';
