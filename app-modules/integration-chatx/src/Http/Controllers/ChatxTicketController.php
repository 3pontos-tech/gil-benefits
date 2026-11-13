<?php

declare(strict_types=1);

namespace TresPontosTech\IntegrationChatx\Http\Controllers;

use App\Enums\InboundWebhookSourceEnum;
use Basement\Webhooks\Actions\StoreInboundWebhook;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * TEMPORARY, for testing only: discovery mode while we learn the real ChatX payload.
 * Every request is logged and stored in Inbound Webhooks, always answered with 200,
 * and no ticket is opened. This will be replaced once the contract is settled:
 * restore ticket creation via OpenTicketFromChatx and the signature check.
 */
final class ChatxTicketController
{
    public function __invoke(Request $request): JsonResponse
    {
        $rawBody = $request->getContent();
        $body = json_decode($rawBody, true);

        $payload = [
            'ip' => $request->ip(),
            'received_at' => now()->toIso8601String(),
            'method' => $request->method(),
            'full_url' => $request->fullUrl(),
            'query' => $request->query(),
            'cookies' => $request->cookies->all(),
            'files' => array_keys($request->allFiles()),
            'input' => $request->all(),
            'headers' => $request->headers->all(),
            'body' => is_array($body) ? $body : null,
            'raw_body' => $rawBody,
        ];

        Log::info('ChatX webhook received.', $payload);

        $event = $request->header('X-ChatX-Event') ?? (is_array($body) && is_string($body['event'] ?? null) ? $body['event'] : null);

        resolve(StoreInboundWebhook::class)->store(
            source: InboundWebhookSourceEnum::Chatx,
            event: $event ?? 'unknown',
            url: $request->fullUrl(),
            payload: $payload,
        );

        return response()->json(['ok' => true]);
    }
}
