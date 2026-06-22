<?php

declare(strict_types=1);

namespace TresPontosTech\PanelCompany\DTOs;

final readonly class CreditTotals
{
    public function __construct(
        public int $available,
        public int $inUse,
        public int $used,
        public int $total,
    ) {}
}
