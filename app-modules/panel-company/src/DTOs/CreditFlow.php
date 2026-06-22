<?php

declare(strict_types=1);

namespace TresPontosTech\PanelCompany\DTOs;

final readonly class CreditFlow
{
    public function __construct(
        public int $distributed,
        public int $usedInPeriod,
    ) {}
}
