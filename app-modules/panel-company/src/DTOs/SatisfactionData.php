<?php

declare(strict_types=1);

namespace TresPontosTech\PanelCompany\DTOs;

final readonly class SatisfactionData
{
    public function __construct(
        public float $avg,
        public int $total,
        public int $nps,
        public float $recommend,
    ) {}
}
