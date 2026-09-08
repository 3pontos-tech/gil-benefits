<?php

declare(strict_types=1);

namespace TresPontosTech\Credits\Events;

use Illuminate\Foundation\Events\Dispatchable;
use TresPontosTech\Credits\DTOs\CreditDTO;

final class CreditConsumed
{
    use Dispatchable;

    public function __construct(
        public readonly CreditDTO $dto,
    ) {}
}
