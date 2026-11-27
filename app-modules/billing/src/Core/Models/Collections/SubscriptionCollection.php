<?php

declare(strict_types=1);

namespace TresPontosTech\Billing\Core\Models\Collections;

use Illuminate\Database\Eloquent\Collection;
use TresPontosTech\Billing\Core\Models\Subscriptions\Subscription;

/**
 * Access is read from our own rows, never from the gateway: every gateway writes its
 * state here through the subscription events, so one rule serves all of them.
 *
 * @extends Collection<int, Subscription>
 */
class SubscriptionCollection extends Collection
{
    public function grantsAccessTo(string $type): bool
    {
        return $this->contains(fn (Subscription $subscription): bool => $subscription->type === $type && $subscription->valid());
    }

    public function grantsAnyAccess(): bool
    {
        return $this->contains(fn (Subscription $subscription): bool => $subscription->valid());
    }
}
