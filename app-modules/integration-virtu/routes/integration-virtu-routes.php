<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use TresPontosTech\IntegrationVirtu\Http\Controllers\VirtuWebhookController;
use TresPontosTech\IntegrationVirtu\Http\Middleware\ValidateVirtuWebhookSignature;

Route::post('/webhooks/virtu', [VirtuWebhookController::class, 'handle'])
    ->name('webhooks.virtu')
    ->withoutMiddleware(['auth', 'verified'])
    ->middleware(ValidateVirtuWebhookSignature::class);
