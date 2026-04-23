<?php

declare(strict_types=1);

use TresPontosTech\Billing\Barte\BarteWebhookController;

Route::post('/webhooks/barte', [BarteWebhookController::class, 'handle'])
    ->name('webhooks.barte')
    ->withoutMiddleware(['auth', 'verified']);
