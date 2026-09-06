<?php

use App\Http\Controllers\Parents\ParentPreferenceController;
use App\Http\Controllers\Parents\ParentPreferenceLinkController;
use Illuminate\Support\Facades\Route;

Route::prefix('parents')->name('parents.')->group(function () {
    Route::get('preferences/request', [ParentPreferenceLinkController::class, 'create'])
        ->name('preferences.request');
    Route::post('preferences/request', [ParentPreferenceLinkController::class, 'store'])
        ->name('preferences.request.store')
        ->middleware('throttle:5,1');

    // No 'auth' middleware anywhere in this group — parents have no
    // account in this app, by design. The signature itself is the only
    // gate on reaching the page; ParentContactPolicy::manage (checked
    // inside the controller) is what stops a logged-in student who
    // obtains a validly-signed link from viewing/editing it.
    Route::middleware('signed')->group(function () {
        Route::get('preferences/{parentContact}', [ParentPreferenceController::class, 'show'])
            ->name('preferences.show');
        Route::post('preferences/{parentContact}', [ParentPreferenceController::class, 'update'])
            ->name('preferences.update');
        Route::post('preferences/{parentContact}/verify-code', [ParentPreferenceController::class, 'verifyCode'])
            ->name('preferences.verify-code');
        Route::post('preferences/{parentContact}/resend-code', [ParentPreferenceController::class, 'resendCode'])
            ->name('preferences.resend-code');
    });
});
