<?php

declare(strict_types=1);

namespace TresPontosTech\Billing\Core\Events\Credit;

use TresPontosTech\Billing\Core\DTOs\CreditOrderDTO;

final class CreditOrderPlaced
{
    public function __construct(
        public readonly CreditOrderDTO $dto,
    ) {}
}
