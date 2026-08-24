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
    public function handle(Request $request): JsonResponse
    {
        $payload = $request->all();

        dispatch(new HandleVirtuWebhookJob($payload));

        resolve(StoreInboundWebhook::class)->store(
            source: InboundWebhookSourceEnum::Virtu,
            event: $payload['event'] ?? 'unknown',
            url: $request->url(),
            payload: $payload,
        );

        return response()->json(['ok' => true]);
    }
}
