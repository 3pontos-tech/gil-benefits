<?php

declare(strict_types=1);

namespace TresPontosTech\Billing\Barte;

use App\Enums\InboundWebhookSourceEnum;
use Basement\Webhooks\Actions\StoreInboundWebhook;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use TresPontosTech\Billing\Barte\Jobs\HandleBarteWebhookJob;

class BarteWebhookController extends Controller
{
    public function handle(Request $request): void
    {
        $payload = $request->all();

        dispatch(new HandleBarteWebhookJob($payload));

        resolve(StoreInboundWebhook::class)->store(
            source: InboundWebhookSourceEnum::Barte,
            event: ($payload['domain']) . '.' . ($payload['status']),
            url: $request->url(),
            payload: $payload,
        );
    }
}
