<?php

namespace TresPontosTech\Billing\Core\Models\Subscriptions;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;
use Laravel\Cashier\Subscription as BaseSubscriptionModel;
use TresPontosTech\Billing\Core\Models\Plan;
use TresPontosTech\Billing\Core\Models\Price;

/**
 * @property string $subscriptionable_type
 * @property string $subscriptionable_id
 * @property string $type
 * @property string $stripe_id
 * @property string $stripe_status
 * @property string|null $stripe_price
 * @property int|null $quantity
 * @property Carbon|null $trial_ends_at
 * @property Carbon|null $ends_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class Subscription extends BaseSubscriptionModel
{
    protected $table = 'billing_subscriptions';

    /**
     * @return MorphTo<Model, $this>
     */
    public function owner(): MorphTo
    {
        return $this->morphTo('subscriptionable');
    }

    /**
     * @return BelongsTo<Price, $this>
     */
    public function price(): BelongsTo
    {
        return $this->belongsTo(Price::class, 'stripe_price', 'provider_price_id');
    }

    /**
     * @return HasOneThrough<Plan, Price, $this>
     */
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
