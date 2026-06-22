<?php

declare(strict_types=1);

namespace TresPontosTech\PanelCompany\DTOs;

final readonly class FunnelStep
{
    public function __construct(
        public string $label,
        public int $value,
        public float $percent,
    ) {}
}
