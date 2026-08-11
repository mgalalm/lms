<?php

use App\Http\Controllers\Teacher\SupportTicketController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'role:teacher'])->prefix('teaching')->name('teaching.')->group(function () {
    Route::post('sessions/{classSession}/support-tickets', [SupportTicketController::class, 'store'])->name('support-tickets.store');
    Route::get('support-tickets/{supportTicket}', [SupportTicketController::class, 'show'])->name('support-tickets.show');
    Route::patch('support-tickets/{supportTicket}/resolve', [SupportTicketController::class, 'resolve'])->name('support-tickets.resolve');
});
