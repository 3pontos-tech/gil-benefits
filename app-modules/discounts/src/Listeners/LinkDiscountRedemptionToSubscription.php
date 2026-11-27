<?php

declare(strict_types=1);

namespace TresPontosTech\Discounts\Listeners;

use TresPontosTech\Billing\Core\DTOs\CheckoutDiscount;
use TresPontosTech\Billing\Core\Events\Subscription\SubscriptionCreated;
use TresPontosTech\Discounts\Enums\DiscountRedemptionStatusEnum;
use TresPontosTech\Discounts\Models\DiscountRedemption;

/**
 * O gateway só revela a referência do checkout ao criar o link, depois da reserva. Quando o checkout
 * traz a reserva exata, ela é ligada direto. Sem ela, a reserva aberta mais recente da pessoa nesse
 * plano é o fallback, que pode errar com dois checkouts concorrentes.
 *
 * A gateway that swaps the checkout reference for its own subscription id while the payment is
 * still pending (Stripe with Pix) moves the reservation along here: the activation that comes
 * later carries only the new id, and the reservation would never be confirmed.
 */
final class LinkDiscountRedemptionToSubscription
{
    public function handle(SubscriptionCreated $event): void
    {
        if ($event->dto->checkoutReference !== null) {
            DiscountRedemption::query()
                ->where('subscription_external_id', $event->dto->checkoutReference)
                ->update(['subscription_external_id' => $event->dto->subscriptionExternalId]);
        }

        if (! $event->dto->discount instanceof CheckoutDiscount) {
            return;
        }

        if ($event->dto->discount->reference !== null) {
            DiscountRedemption::query()
                ->whereKey($event->dto->discount->reference)
                ->whereNull('subscription_external_id')
                ->update(['subscription_external_id' => $event->dto->subscriptionExternalId]);

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
