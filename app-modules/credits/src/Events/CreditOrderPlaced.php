<?php

declare(strict_types=1);

namespace TresPontosTech\Credits\Events;

use TresPontosTech\Credits\DTOs\CreditOrderDTO;

final class CreditOrderPlaced
{
    public function __construct(
        public readonly CreditOrderDTO $dto,
    ) {}
}
