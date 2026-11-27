<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Laravel\Nightwatch\Http\Middleware\Sample;
use TresPontosTech\IntegrationStripe\Http\Controllers\StripeWebhookController;
use TresPontosTech\IntegrationStripe\Http\Middleware\ValidateStripeWebhookSignature;

Route::post('/webhooks/stripe', [StripeWebhookController::class, 'handle'])
    ->name('webhooks.stripe')
    ->withoutMiddleware(['auth', 'verified'])
    ->middleware([Sample::always(), ValidateStripeWebhookSignature::class]);
