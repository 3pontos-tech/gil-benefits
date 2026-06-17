<?php

declare(strict_types=1);

namespace TresPontosTech\PanelCompany\DTOs;

final readonly class StatusBreakdown
{
    /**
     * @param  array<int, StatusSegment>  $segments
     */
    public function __construct(
        public array $segments,
        public float $completedPercent,
        public int $total,
    ) {}
}
