<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Laravel\Nightwatch\Http\Middleware\Sample;
use TresPontosTech\IntegrationVirtu\Http\Controllers\VirtuWebhookController;
use TresPontosTech\IntegrationVirtu\Http\Middleware\ValidateVirtuWebhookSignature;

Route::post('/webhooks/virtu', [VirtuWebhookController::class, 'handle'])
    ->name('webhooks.virtu')
    ->withoutMiddleware(['auth', 'verified'])
    ->middleware([Sample::always(), ValidateVirtuWebhookSignature::class]);
