<?php

namespace TresPontosTech\Billing\Core\Models\Subscriptions;

use Illuminate\Database\Eloquent\Relations\MorphTo;
use Laravel\Cashier\Subscription as BaseSubscriptionModel;

class Subscription extends BaseSubscriptionModel
{
    protected $table = 'billing_subscriptions';

    public function owner(): MorphTo
    {
        return $this->morphTo('subscriptionable');
    }
}
