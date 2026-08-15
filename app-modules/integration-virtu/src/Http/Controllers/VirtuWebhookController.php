<?php

declare(strict_types=1);

namespace TresPontosTech\IntegrationVirtu\Http\Controllers;

use App\Enums\InboundWebhookSourceEnum;
use Basement\Webhooks\Actions\StoreInboundWebhook;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use TresPontosTech\IntegrationVirtu\DTO\VirtuWebhookDTO;
use TresPontosTech\IntegrationVirtu\Jobs\HandleVirtuWebhookJob;

final class VirtuWebhookController
{
    public function handle(Request $request): JsonResponse
    {
        $payload = $request->all();

        // TEMPORÁRIO — a leitura já interpretada, ao lado do corpo cru que o
        // middleware gravou. É aqui que se vê se o checkoutId veio e com que
        // prefixo. Remover junto com os comandos virtu:probe.
        $dto = VirtuWebhookDTO::fromArray($payload);

        Log::channel('virtu')->debug('Webhook Virtu interpretado.', [
            'event' => $dto->event?->value,
            'checkout_id' => $dto->checkoutId,
            'sale_id' => $dto->saleId,
            'status' => $dto->status,
            'payment_status' => $dto->paymentStatus,
            'is_paid' => $dto->isPaid(),
            'customer_tax_id' => $dto->customerTaxId,
            'customer_email' => $dto->customerEmail,
            'is_subscription_charge' => $dto->isSubscriptionCharge(),
            'subscriptions' => $dto->subscriptions,
        ]);

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
