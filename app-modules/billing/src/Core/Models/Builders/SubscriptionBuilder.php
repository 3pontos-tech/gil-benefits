<?php

declare(strict_types=1);

namespace TresPontosTech\Billing\Core\Models\Builders;

use Illuminate\Database\Eloquent\Builder;
use TresPontosTech\Billing\Core\Models\Subscriptions\Subscription;

/**
 * @extends Builder<Subscription>
 */
class SubscriptionBuilder extends Builder
{
    /**
     * Filters by status only. Looser than Subscription::valid(): an `active` row whose
     * paid period is over still passes here, because the dates are not read.
     */
    public function grantingAccess(): self
    {
        return $this->whereNotIn('stripe_status', Subscription::STATUSES_WITHOUT_ACCESS);
    }

    /**
     * The subscription that pays for the person right now, in SQL: `active` or `defaulter`
     * (still retried by the gateway) with no paid period already over. Same rule as
     * Subscription::active(), so a delinquent person keeps the monthly quota along with access.
     */
    public function current(): self
    {
        return $this
            ->whereIn('stripe_status', [Subscription::STATUS_ACTIVE, Subscription::STATUS_DEFAULTER])
            ->where(fn (Builder $query) => $query->whereNull('ends_at')->orWhere('ends_at', '>', now()));
    }
}
