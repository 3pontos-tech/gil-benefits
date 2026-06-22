<?php

declare(strict_types=1);

namespace TresPontosTech\PanelCompany\DTOs;

final readonly class FunnelData
{
    /**
     * @param  array<int, FunnelStep>  $steps
     */
    public function __construct(
        public int $invited,
        public int $withAccess,
        public int $withPlan,
        public float $adoptionRate,
        public int $noAccess,
        public int $accessNoPlan,
        public int $newThisMonth,
        public array $steps,
    ) {}
}
