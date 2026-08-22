<?php

declare(strict_types=1);

namespace TresPontosTech\PanelCompany\DTOs;

final readonly class AppointmentStats
{
    public function __construct(
        public int $total,
        public int $completed,
        public int $cancelled,
        public int $finalized,
        public float $attendanceRate,
    ) {}
}
