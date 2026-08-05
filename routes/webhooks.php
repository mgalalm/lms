<?php

use App\Http\Controllers\ZoomWebhookController;
use App\Http\Middleware\VerifyZoomWebhookSignature;
use Illuminate\Support\Facades\Route;

Route::post('webhooks/zoom', ZoomWebhookController::class)
    ->middleware(VerifyZoomWebhookSignature::class)
    ->name('webhooks.zoom');
