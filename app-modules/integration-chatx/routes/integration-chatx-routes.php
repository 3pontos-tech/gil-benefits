<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use TresPontosTech\IntegrationChatx\Http\Controllers\ChatxTicketController;
use TresPontosTech\IntegrationChatx\Http\Middleware\ValidateChatxRequest;

Route::post('/webhooks/chatx', ChatxTicketController::class)
    ->name('webhooks.chatx')
    ->withoutMiddleware(['auth', 'verified'])
    ->middleware(ValidateChatxRequest::class);
