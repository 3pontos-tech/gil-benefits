<?php

declare(strict_types=1);

namespace TresPontosTech\Billing\Barte\Actions;

use App\Models\Users\User;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Log;
use TresPontosTech\Billing\Barte\DTOs\BarteWebhookDto;
use TresPontosTech\Billing\Barte\Enums\BarteWebhookEventEnum;
use TresPontosTech\Billing\Core\DTOs\SubscriptionDTO;
use TresPontosTech\Billing\Core\Enums\BillingProviderEnum;
use TresPontosTech\Billing\Core\Events\Credit\OrderCreditPurchased;
use TresPontosTech\Billing\Core\Events\Subscription\SubscriptionActivated;
use TresPontosTech\Billing\Core\Events\Subscription\SubscriptionCancelled;
use TresPontosTech\Billing\Core\Events\Subscription\SubscriptionCreated;
use TresPontosTech\Billing\Core\Events\Subscription\SubscriptionDefaulted;
use TresPontosTech\Billing\Core\Models\BillingCustomer;
use TresPontosTech\Billing\Core\Models\CreditOrder;
use TresPontosTech\Billing\Core\Models\Plan;
use TresPontosTech\Company\Models\Company;

class HandleBarteWebhook
{
    public function handle(BarteWebhookDto $dto): void
    {
        match ($dto->domain) {
            'SUBSCRIPTION' => $this->handleSubscription($dto),
            'ORDER' => $this->handleOrder($dto),
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
        $priceId = $dto->metadata->get('barte_price_id');

        $quantity = $dto->metadata->get('quantity');

        $planSlug = $planUuid
            ? (Plan::query()->where('provider_product_id', $planUuid)->value('slug') ?? 'default')
            : 'default';

        $event = match ($dto->event) {
            BarteWebhookEventEnum::SubscriptionPending => new SubscriptionCreated(SubscriptionDTO::make($billingCustomer, $dto->uuid, 'pending', $planUuid, $cycleType, $quantity, planSlug: $planSlug, priceId: $priceId)),
            BarteWebhookEventEnum::SubscriptionActive => new SubscriptionActivated(SubscriptionDTO::make($billingCustomer, $dto->uuid, 'active', $planUuid, $cycleType, $quantity, planSlug: $planSlug, priceId: $priceId)),
            BarteWebhookEventEnum::SubscriptionDefaulter => new SubscriptionDefaulted(SubscriptionDTO::make($billingCustomer, $dto->uuid, 'defaulter', $planUuid, $cycleType, $quantity, planSlug: $planSlug, priceId: $priceId)),
            BarteWebhookEventEnum::SubscriptionInactive => new SubscriptionCancelled(SubscriptionDTO::make($billingCustomer, $dto->uuid, 'inactive', $planUuid, $cycleType, $quantity, Date::now(), $planSlug, priceId: $priceId)),
            default => null,
        };

        if ($event === null) {
            return;
        }

        event($event);
    }

    private function handleOrder(BarteWebhookDto $dto): void
    {
        if (! in_array($dto->event, [BarteWebhookEventEnum::OrderSent, BarteWebhookEventEnum::OrderPaid], true)) {
            return;
        }

        $order = $this->resolveCreditOrder($dto);

        if (! $order instanceof CreditOrder) {
            Log::warning('Barte ORDER webhook sem pedido de crédito correspondente', ['uuid' => $dto->uuid]);

            return;
        }

        if ($dto->event === BarteWebhookEventEnum::OrderSent) {
            $this->notifyOrderPending($order->billable_type, $order->billable_id, $order->quantity);

            return;
        }

        event(new OrderCreditPurchased(creditOrderId: $order->getKey()));
    }

    private function resolveCreditOrder(BarteWebhookDto $dto): ?CreditOrder
    {
        $creditOrderId = $dto->metadata->get('credit_order_id');

        if (! is_string($creditOrderId) || blank($creditOrderId)) {
            return null;
        }

        return CreditOrder::query()->find($creditOrderId);
    }

    private function notifyOrderPending(string $billableType, string $billableId, int $quantity): void
    {
        $modelClass = Relation::getMorphedModel($billableType);

        if ($modelClass === null) {
            return;
        }

        $billable = $modelClass::query()->findOrFail($billableId);

        if ($billable instanceof User) {
            $owner = $billable;
        } elseif ($billable instanceof Company) {
            $owner = $billable->owner;
        } else {
            return;
        }

        Notification::make()
            ->title(__('billing::notifications.credit_order_created.title'))
            ->body(__('billing::notifications.credit_order_created.body', ['quantity' => $quantity]))
            ->warning()
            ->sendToDatabase($owner);
    }
}
