<?php

declare(strict_types=1);

namespace TresPontosTech\Billing\Core\Listeners\Credit;

use TresPontosTech\Billing\Core\Events\Credit\OrderCreditPurchased;
use TresPontosTech\Billing\Core\Jobs\ProcessCreditPurchaseJob;

class DispatchCreditPurchaseJob
{
    public function handle(OrderCreditPurchased $event): void
    {
        dispatch(new ProcessCreditPurchaseJob($event));
    }
}
