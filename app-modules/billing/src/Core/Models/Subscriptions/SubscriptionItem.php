<?php

declare(strict_types=1);

namespace TresPontosTech\Billing\Core\Models\Subscriptions;

use Laravel\Cashier\SubscriptionItem as BaseSubscriptionItem;

class SubscriptionItem extends BaseSubscriptionItem
{
    protected $table = 'billing_subscription_items';
}
