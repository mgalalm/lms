<?php

use App\Http\Controllers\ImpersonationController;
use Illuminate\Support\Facades\Route;

Route::get('impersonate/{user}', [ImpersonationController::class, 'store'])
    ->middleware(['auth', 'verified', 'role:admin'])
    ->name('impersonate.start');

Route::post('impersonate/stop', [ImpersonationController::class, 'destroy'])
    ->middleware(['auth', 'verified'])
    ->name('impersonate.stop');
