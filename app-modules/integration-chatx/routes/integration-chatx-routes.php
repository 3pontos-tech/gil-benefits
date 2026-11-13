<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use TresPontosTech\IntegrationChatx\Http\Controllers\ChatxTicketController;

// use TresPontosTech\IntegrationChatx\Http\Middleware\ValidateChatxRequest;

/*
 * TEMPORARY, for testing only: ValidateChatxRequest is disabled while we discover the
 * ChatX payload format. It must be re-enabled (adapted to X-ChatX-Signature) before
 * this endpoint is trusted.
 */
Route::post('/webhooks/chatx', ChatxTicketController::class)
    ->name('webhooks.chatx')
    ->withoutMiddleware(['auth', 'verified']);
// ->middleware(ValidateChatxRequest::class);
