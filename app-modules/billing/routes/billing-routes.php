<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use TresPontosTech\Billing\Barte\BarteWebhookController;
use TresPontosTech\Billing\Barte\Http\Middleware\ValidateBarteWebhookSecret;

Route::post('/webhooks/barte', [BarteWebhookController::class, 'handle'])
    ->name('webhooks.barte')
    ->withoutMiddleware(['auth', 'verified'])
    ->middleware(ValidateBarteWebhookSecret::class);
