<?php

namespace TresPontosTech\Billing\Core\Models\Subscriptions;

use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Laravel\Cashier\Subscription as BaseSubscriptionModel;
use TresPontosTech\Billing\Core\Models\Plan;
use TresPontosTech\Billing\Core\Models\Price;
use TresPontosTech\Billing\Policies\SubscriptionPolicy;

#[UsePolicy(SubscriptionPolicy::class)]
class Subscription extends BaseSubscriptionModel
{
    protected $table = 'billing_subscriptions';

    public function owner(): MorphTo
    {
        return $this->morphTo('subscriptionable');
    }

    public function price(): BelongsTo
    {
        return $this->belongsTo(Price::class, 'stripe_price', 'provider_price_id');
    }

    public function plan(): HasOneThrough
    {
        return $this->hasOneThrough(
            Plan::class,
            Price::class,
            'provider_price_id',
            'id',
            'stripe_price',
            'billing_plan_id'
        );
    }
}
