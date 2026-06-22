<?php

declare(strict_types=1);

namespace TresPontosTech\PanelCompany\DTOs;

final readonly class EngagementData
{
    public function __construct(
        public int $totalEmployees,
        public int $activeUsers,
        public int $inactiveUsers,
        public float $utilizationRate,
        public int $firstTimeUsers,
    ) {}
}
