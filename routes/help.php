<?php

use App\Http\Controllers\HelpController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->prefix('help')->name('help.')->group(function () {
    Route::get('/', [HelpController::class, 'index'])->name('index');
    Route::get('/{article}', [HelpController::class, 'show'])->name('show');
});
