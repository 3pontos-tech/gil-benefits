<?php

declare(strict_types=1);

namespace TresPontosTech\PanelAdmin\DTOs;

use TresPontosTech\PanelAdmin\DTOs\Concerns\CalculatesEngagementRates;

/**
 * Consolidated engagement figures across every company in scope.
 */
final readonly class EngagementTotals
{
    use CalculatesEngagementRates;

    public function __construct(
        public int $seats,
        public int $registered,
        public int $withAppointment,
        public int $withCompletedAppointment,
        public int $withRecurrence,
    ) {}
}
