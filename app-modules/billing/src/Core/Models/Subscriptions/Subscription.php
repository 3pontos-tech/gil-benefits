<?php

namespace TresPontosTech\Billing\Core\Models\Subscriptions;

use App\Models\Users\User;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;
use Laravel\Cashier\Subscription as BaseSubscriptionModel;
use TresPontosTech\Billing\Core\Models\Plan;
use TresPontosTech\Billing\Core\Models\Price;
use TresPontosTech\Billing\Database\Factories\SubscriptionFactory;
use TresPontosTech\Company\Models\Company;

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
#[UseFactory(SubscriptionFactory::class)]
class Subscription extends BaseSubscriptionModel
{
    public const array STATUSES_WITHOUT_ACCESS = ['pending', 'inactive', 'defaulter'];

    protected $table = 'billing_subscriptions';

    public function active(): bool
    {
        return ! $this->deniesAccessByStatus() && parent::active();
    }

    public function valid(): bool
    {
        return ! $this->deniesAccessByStatus() && parent::valid();
    }

    /**
     * @param  Builder<$this>  $query
     * @return Builder<$this>
     */
    protected function scopeGrantingAccess(Builder $query): Builder
    {
        return $query->whereNotIn('stripe_status', self::STATUSES_WITHOUT_ACCESS);
    }

    public static function grantsAccess(Company|User $billable, string $type): bool
    {
        return $billable->subscriptions
            ->where('type', $type)
            ->contains(fn (self $subscription): bool => $subscription->valid());
    }

    private function deniesAccessByStatus(): bool
    {
        return in_array($this->stripe_status, self::STATUSES_WITHOUT_ACCESS, true);
    }

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
