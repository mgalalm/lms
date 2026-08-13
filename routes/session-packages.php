<?php

use App\Http\Controllers\Student\SessionPackageCheckoutController;
use App\Http\Controllers\Student\SessionPackagesController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'role:student'])->prefix('session-packages')->name('session-packages.')->group(function () {
    Route::get('/', [SessionPackagesController::class, 'index'])->name('index');
    Route::get('/{sessionPackage}/checkout', [SessionPackageCheckoutController::class, 'store'])->name('checkout');
    Route::get('/checkout/success', [SessionPackageCheckoutController::class, 'success'])->name('checkout.success');
    Route::get('/checkout/{purchase}/cancel', [SessionPackageCheckoutController::class, 'cancel'])->name('checkout.cancel');
});
