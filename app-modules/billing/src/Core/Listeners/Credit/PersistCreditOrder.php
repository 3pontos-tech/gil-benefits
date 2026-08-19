<?php

declare(strict_types=1);

namespace TresPontosTech\Billing\Core\Listeners\Credit;

use TresPontosTech\Billing\Core\Actions\Credit\StartCreditOrder;
use TresPontosTech\Billing\Core\Events\Credit\CreditOrderPlaced;

class PersistCreditOrder
{
    public function __construct(private readonly StartCreditOrder $action) {}

    public function handle(CreditOrderPlaced $event): void
    {
        $this->action->handle(
            provider: $event->dto->provider,
            billable: $event->dto->billable,
            company: $event->dto->company,
            quantity: $event->dto->quantity,
            checkoutId: $event->dto->checkoutId,
        );
    }
}
