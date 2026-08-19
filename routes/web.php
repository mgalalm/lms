<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Teacher\PingParentController;
use App\Http\Controllers\Teacher\SessionsScheduleController;
use App\Http\Resources\SessionPackageResource;
use App\Models\SessionPackage;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'packages' => SessionPackageResource::collection(SessionPackage::purchasable()->get()),
    ]);
})->name('home');

Route::get('dashboard', DashboardController::class)
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware(['auth', 'verified', 'role:teacher'])->prefix('teaching')->name('teaching.')->group(function () {
    Route::get('sessions-schedule', SessionsScheduleController::class)->name('sessions-schedule');
    Route::post('sessions/{classSession}/ping-parent', PingParentController::class)->name('sessions.ping-parent');
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
require __DIR__.'/webhooks.php';
require __DIR__.'/community.php';
require __DIR__.'/lms.php';
require __DIR__.'/insights.php';
require __DIR__.'/achievements.php';
require __DIR__.'/session-feedback.php';
require __DIR__.'/session-recaps.php';
require __DIR__.'/lesson-videos.php';
require __DIR__.'/referral-codes.php';
require __DIR__.'/payroll.php';
require __DIR__.'/requests.php';
require __DIR__.'/notifications.php';
require __DIR__.'/support-tickets.php';
require __DIR__.'/help.php';
require __DIR__.'/session-packages.php';
