<?php

declare(strict_types=1);

namespace TresPontosTech\Credits\Listeners;

use TresPontosTech\Credits\Actions\ConsumeCredit;
use TresPontosTech\Credits\Events\CreditConsumed;

class ConsumeCreditListener
{
    public function handle(CreditConsumed $event): void
    {
        resolve(ConsumeCredit::class)->execute($event->dto);
    }
}
