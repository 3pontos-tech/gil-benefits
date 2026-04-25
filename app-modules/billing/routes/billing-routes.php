<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use TresPontosTech\Billing\Barte\BarteWebhookController;

Route::post('/webhooks/barte', [BarteWebhookController::class, 'handle'])
    ->name('webhooks.barte')
    ->withoutMiddleware(['auth', 'verified']);
