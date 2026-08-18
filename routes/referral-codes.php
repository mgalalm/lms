<?php

use App\Http\Controllers\ReferralCodeValidationController;
use Illuminate\Support\Facades\Route;

Route::post('referral-codes/validate', ReferralCodeValidationController::class)
    ->middleware('throttle:10,1')
    ->name('referral-codes.validate');
