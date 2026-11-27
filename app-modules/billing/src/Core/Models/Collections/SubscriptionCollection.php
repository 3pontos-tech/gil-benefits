<?php

declare(strict_types=1);

namespace TresPontosTech\Billing\Core\Models\Collections;

use Illuminate\Database\Eloquent\Collection;
use TresPontosTech\Billing\Core\Models\Builders\SubscriptionBuilder;
use TresPontosTech\Billing\Core\Models\Subscriptions\Subscription;

/**
 * Access is read from our own rows, never from the gateway: every gateway writes its
 * state here through the subscription events, so one rule serves all of them.
 *
 * @extends Collection<int, Subscription>
 */
class SubscriptionCollection extends Collection
{
    /** @var list<string>|null */
    private ?array $retiredGatewayPriceIds = null;

    public function grantsAccessTo(string $type): bool
    {
        return $this->contains(fn (Subscription $subscription): bool => $subscription->type === $type && $this->grants($subscription));
    }

    public function grantsAnyAccess(): bool
    {
        return $this->contains(fn (Subscription $subscription): bool => $this->grants($subscription));
    }

    private function grants(Subscription $subscription): bool
    {
        return $subscription->valid()
            && ! in_array($subscription->stripe_price, $this->retiredGatewayPriceIds(), true);
    }

    /**
     * Read once per collection: the middlewares ask once per plan.
     *
     * @return list<string>
     */
    private function retiredGatewayPriceIds(): array
    {
        if ($this->retiredGatewayPriceIds !== null) {
            return $this->retiredGatewayPriceIds;
        }

        $prices = $this->pluck('stripe_price')->filter()->unique()->values();

        $retiredPriceIds = $prices->isEmpty()
            ? []
            : SubscriptionBuilder::retiredGatewayPriceIds()
                ->whereIn('provider_price_id', $prices)
                ->pluck('provider_price_id')
                ->all();

        return $this->retiredGatewayPriceIds = array_values(array_filter($retiredPriceIds, is_string(...)));
    }
}
