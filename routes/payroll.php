<?php

use App\Http\Controllers\Teacher\PayrollController;
use Illuminate\Support\Facades\Route;

Route::get('payroll', PayrollController::class)
    ->middleware(['auth', 'verified', 'role:teacher'])
    ->name('payroll.index');
