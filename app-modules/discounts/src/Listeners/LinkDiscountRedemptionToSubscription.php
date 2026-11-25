<?php

declare(strict_types=1);

namespace TresPontosTech\Discounts\Listeners;

use TresPontosTech\Billing\Core\DTOs\CheckoutDiscount;
use TresPontosTech\Billing\Core\Events\Subscription\SubscriptionCreated;
use TresPontosTech\Discounts\Enums\DiscountRedemptionStatusEnum;
use TresPontosTech\Discounts\Models\DiscountRedemption;

/**
 * O gateway só revela a referência do checkout ao criar o link, depois da reserva. Este listener
 * prende a reserva aberta da pessoa nesse plano à assinatura pendente, para o pagamento confirmar a reserva certa.
 */
final class LinkDiscountRedemptionToSubscription
{
    public function handle(SubscriptionCreated $event): void
    {
        if (! $event->dto->discount instanceof CheckoutDiscount) {
            return;
        }

        DiscountRedemption::query()
            ->where('billable_type', $event->dto->billableType)
            ->where('billable_id', $event->dto->billableId)
            ->where('plan_slug', $event->dto->planSlug)
            ->where('status', DiscountRedemptionStatusEnum::Reserved)
            ->whereNull('subscription_external_id')
            ->where('reserved_until', '>', now())
            ->latest('created_at')
            ->first()
            ?->update(['subscription_external_id' => $event->dto->subscriptionExternalId]);
    }
}
