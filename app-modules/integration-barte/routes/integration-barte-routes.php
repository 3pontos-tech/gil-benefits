<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Laravel\Nightwatch\Http\Middleware\Sample;
use TresPontosTech\IntegrationBarte\BarteWebhookController;
use TresPontosTech\IntegrationBarte\Http\Middleware\ValidateBarteWebhookSecret;

Route::post('/webhooks/barte', [BarteWebhookController::class, 'handle'])
    ->name('webhooks.barte')
    ->withoutMiddleware(['auth', 'verified'])
    ->middleware([Sample::always(), ValidateBarteWebhookSecret::class]);
