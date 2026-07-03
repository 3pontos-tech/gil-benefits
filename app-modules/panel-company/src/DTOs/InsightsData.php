<?php

declare(strict_types=1);

namespace TresPontosTech\PanelCompany\DTOs;

final readonly class InsightsData
{
    public function __construct(
        public int $neverUsedCount,
        public int $totalEmployees,
        public float $neverUsedRate,
        public VolumeVariation $volume,
        public ?TopUser $topUser,
    ) {}
}
