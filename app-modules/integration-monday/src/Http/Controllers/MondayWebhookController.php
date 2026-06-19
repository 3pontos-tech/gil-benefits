<?php

declare(strict_types=1);

namespace TresPontosTech\IntegrationMonday\Http\Controllers;

use App\Enums\InboundWebhookSourceEnum;
use Basement\Webhooks\Actions\StoreInboundWebhook;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use TresPontosTech\IntegrationMonday\Jobs\HandleMondayWebhookJob;

final class MondayWebhookController
{
    public function handle(Request $request): JsonResponse
    {
        // On registration Monday POSTs a challenge that must be echoed back verbatim.
        if ($request->has('challenge')) {
            return response()->json(['challenge' => $request->input('challenge')]);
        }

        $payload = $request->all();

        dispatch(new HandleMondayWebhookJob($payload));

        resolve(StoreInboundWebhook::class)->store(
            source: InboundWebhookSourceEnum::Monday,
            event: $payload['event']['type'] ?? 'unknown',
            url: $request->url(),
            payload: $payload,
        );

        return response()->json(['ok' => true]);
    }
}
