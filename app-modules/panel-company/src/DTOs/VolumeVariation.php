<?php

declare(strict_types=1);

namespace TresPontosTech\PanelCompany\DTOs;

final readonly class VolumeVariation
{
    public function __construct(
        public int $current,
        public int $previous,
        public ?float $variation,
    ) {}
}
