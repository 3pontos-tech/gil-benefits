<?php

declare(strict_types=1);

namespace TresPontosTech\Billing\Core\Models\Subscriptions;

use Illuminate\Support\Carbon;
use Laravel\Cashier\SubscriptionItem as BaseSubscriptionItem;

/**
 * @property int $id
 * @property int $subscription_id
 * @property string $stripe_id
 * @property string|null $meter_id
 * @property string|null $meter_event_name
 * @property string $stripe_product
 * @property string $stripe_price
 * @property int|null $quantity
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class SubscriptionItem extends BaseSubscriptionItem
{
    protected $table = 'billing_subscription_items';
}
