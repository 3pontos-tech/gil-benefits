<?php

declare(strict_types=1);

namespace TresPontosTech\Billing\Core\Events\Credit;

final class CreditsDelivered
{
    public function __construct(
        public readonly string $ownerId,
        public readonly int $quantity,
    ) {}
}
