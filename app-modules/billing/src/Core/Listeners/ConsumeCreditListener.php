<?php

declare(strict_types=1);

namespace TresPontosTech\Billing\Core\Listeners;

use TresPontosTech\Billing\Core\Actions\Credit\ConsumeCredit;
use TresPontosTech\Billing\Core\Events\Credit\CreditConsumed;

class ConsumeCreditListener
{
    public function handle(CreditConsumed $event): void
    {
        resolve(ConsumeCredit::class)->execute($event->dto);
    }
}
