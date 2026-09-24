<?php

declare(strict_types=1);

namespace TresPontosTech\Credits\Listeners;

use TresPontosTech\Credits\Events\OrderCreditPurchased;
use TresPontosTech\Credits\Jobs\ProcessCreditPurchaseJob;

class DispatchCreditPurchaseJob
{
    public function handle(OrderCreditPurchased $event): void
    {
        dispatch(new ProcessCreditPurchaseJob($event));
    }
}
