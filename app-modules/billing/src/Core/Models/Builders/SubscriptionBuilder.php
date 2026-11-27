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
}
