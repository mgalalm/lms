<?php

use App\Http\Controllers\Teacher\RequestAttachmentController;
use App\Http\Controllers\Teacher\RequestsController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'role:teacher'])->prefix('requests')->name('requests.')->group(function () {
    Route::get('/', [RequestsController::class, 'index'])->name('index');
    Route::post('/', [RequestsController::class, 'store'])->name('store');
});

Route::get('requests/{teacherRequest}/attachment', RequestAttachmentController::class)
    ->middleware(['auth', 'verified'])
    ->name('requests.attachment.show');
