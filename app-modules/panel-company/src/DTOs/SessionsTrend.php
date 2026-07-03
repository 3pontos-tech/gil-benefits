<?php

declare(strict_types=1);

namespace TresPontosTech\PanelCompany\DTOs;

final readonly class SessionsTrend
{
    /**
     * @param  array<int, int>  $totalSeries
     * @param  array<int, int>  $completedSeries
     * @param  array<int, string>  $labels
     */
    public function __construct(
        public array $totalSeries,
        public array $completedSeries,
        public array $labels,
        public int $completedTotal,
        public ?float $growthFactor,
    ) {}
}
