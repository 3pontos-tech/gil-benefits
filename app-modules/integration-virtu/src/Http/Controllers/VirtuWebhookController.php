<?php

declare(strict_types=1);

namespace TresPontosTech\IntegrationVirtu\Http\Controllers;

use App\Enums\InboundWebhookSourceEnum;
use Basement\Webhooks\Actions\StoreInboundWebhook;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use TresPontosTech\IntegrationVirtu\Jobs\HandleVirtuWebhookJob;

final class VirtuWebhookController
{
    /**
     * Stores the raw payload before dispatching: the job burns the idempotency
     * key, so a failure to store after dispatching would answer 500, make Virtu
     * redeliver, and have the redelivery dropped as already processed — leaving
     * nothing to reconcile from.
     */
    public function handle(Request $request): JsonResponse
    {
        $payload = $request->all();

        resolve(StoreInboundWebhook::class)->store(
            source: InboundWebhookSourceEnum::Virtu,
            event: $payload['event'] ?? 'unknown',
            url: $request->url(),
            payload: $payload,
        );

        dispatch(new HandleVirtuWebhookJob($payload));

        return response()->json(['ok' => true]);
    }
}
