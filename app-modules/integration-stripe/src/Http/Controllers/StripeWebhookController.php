<?php

declare(strict_types=1);

namespace TresPontosTech\IntegrationStripe\Http\Controllers;

use App\Enums\InboundWebhookSourceEnum;
use Basement\Webhooks\Actions\StoreInboundWebhook;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use TresPontosTech\IntegrationStripe\DTO\StripeWebhookDTO;
use TresPontosTech\IntegrationStripe\Jobs\HandleStripeWebhookJob;

final class StripeWebhookController
{
    /**
     * Stores the raw payload before dispatching: the job burns the event id, so a failure
     * to store after dispatching would leave nothing to reconcile from.
     */
    public function handle(Request $request): JsonResponse
    {
        $payload = $request->all();

        resolve(StoreInboundWebhook::class)->store(
            source: InboundWebhookSourceEnum::Stripe,
            event: $payload['type'] ?? 'unknown',
            url: $request->url(),
            payload: $payload,
        );

        dispatch(new HandleStripeWebhookJob(StripeWebhookDTO::fromPayload($payload)));

        return response()->json(['ok' => true]);
    }
}
