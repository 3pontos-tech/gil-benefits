<?php

declare(strict_types=1);

namespace TresPontosTech\Billing\Core\Events\Credit;

use Illuminate\Foundation\Events\Dispatchable;
use TresPontosTech\Billing\Core\DTOs\CreditDTO;

final class CreditConsumed
{
    use Dispatchable;

    public function __construct(
        public readonly CreditDTO $dto,
    ) {}
}
