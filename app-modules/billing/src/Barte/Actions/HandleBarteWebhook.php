<?php

declare(strict_types=1);

namespace TresPontosTech\Billing\Barte\Actions;

use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Log;
use TresPontosTech\Billing\Barte\DTOs\BarteWebhookDto;
use TresPontosTech\Billing\Barte\Enums\BarteWebhookEventEnum;
use TresPontosTech\Billing\Core\DTOs\SubscriptionDTO;
use TresPontosTech\Billing\Core\Enums\BillingProviderEnum;
use TresPontosTech\Billing\Core\Events\Subscription\SubscriptionActivated;
use TresPontosTech\Billing\Core\Events\Subscription\SubscriptionCancelled;
use TresPontosTech\Billing\Core\Events\Subscription\SubscriptionCreated;
use TresPontosTech\Billing\Core\Events\Subscription\SubscriptionDefaulted;
use TresPontosTech\Billing\Core\Models\BillingCustomer;

class HandleBarteWebhook
{
    public function handle(BarteWebhookDto $dto): void
    {
        match ($dto->domain) {
            'SUBSCRIPTION' => $this->handleSubscription($dto),
            default => null,
        };
    }

    private function handleSubscription(BarteWebhookDto $dto): void
    {
        if (! $dto->uuidBuyer) {
            Log::warning('Barte webhook sem uuidBuyer', ['uuid' => $dto->uuid]);

            return;
        }

        $billingCustomer = BillingCustomer::query()
            ->where('provider', BillingProviderEnum::Barte)
            ->where('provider_customer_id', $dto->uuidBuyer)
            ->first();

        if (! $billingCustomer) {
            Log::warning('Barte webhook: BillingCustomer não encontrado', ['uuidBuyer' => $dto->uuidBuyer]);

            return;
        }

        $planUuid = $dto->metadata->get('barte_plan_uuid');
        $cycleType = $dto->metadata->get('barte_cycle_type');

        $quantity = $dto->metadata->get('quantity');

        $event = match ($dto->event) {
            BarteWebhookEventEnum::SubscriptionPending => new SubscriptionCreated(SubscriptionDTO::make($billingCustomer, $dto->uuid, 'pending', $planUuid, $cycleType, $quantity)),
            BarteWebhookEventEnum::SubscriptionActive => new SubscriptionActivated(SubscriptionDTO::make($billingCustomer, $dto->uuid, 'active', $planUuid, $cycleType, $quantity)),
            BarteWebhookEventEnum::SubscriptionDefaulter => new SubscriptionDefaulted(SubscriptionDTO::make($billingCustomer, $dto->uuid, 'defaulter', $planUuid, $cycleType, $quantity)),
            BarteWebhookEventEnum::SubscriptionInactive => new SubscriptionCancelled(SubscriptionDTO::make($billingCustomer, $dto->uuid, 'inactive', $planUuid, $cycleType, $quantity, Date::now())),
            default => null,
        };

        if ($event === null) {
            return;
        }

        event($event);
    }
}
