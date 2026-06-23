<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use TresPontosTech\IntegrationMonday\Http\Controllers\MondayWebhookController;
use TresPontosTech\IntegrationMonday\Http\Middleware\ValidateMondayWebhookSecret;

Route::post('/webhooks/monday', [MondayWebhookController::class, 'handle'])
    ->name('webhooks.monday')
    ->withoutMiddleware(['auth', 'verified'])
    ->middleware(ValidateMondayWebhookSecret::class);
