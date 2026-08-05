<?php

use App\Http\Controllers\Community\ChannelController;
use App\Http\Controllers\Community\MessageController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->prefix('community')->name('community.')->group(function () {
    Route::get('/', [ChannelController::class, 'index'])->name('index');
    Route::get('/{channel}', [ChannelController::class, 'show'])->name('show');
    Route::patch('/{channel}/read', [ChannelController::class, 'markRead'])->name('read');
    Route::post('/{channel}/messages', [MessageController::class, 'store'])->name('messages.store');
});
