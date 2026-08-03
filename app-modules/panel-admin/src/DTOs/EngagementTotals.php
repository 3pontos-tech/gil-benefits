<?php

declare(strict_types=1);

namespace TresPontosTech\PanelAdmin\DTOs;

use TresPontosTech\PanelAdmin\Support\EngagementNumber;

/**
 * Consolidated engagement figures across every company in scope.
 */
final readonly class EngagementTotals
{
    public function __construct(
        public int $seats,
        public int $registered,
        public int $withAppointment,
        public int $withCompletedAppointment,
        public int $withRecurrence,
    ) {}

    public function registrationRate(): ?float
    {
        return EngagementNumber::rate($this->registered, $this->seats);
    }

    public function schedulingRate(): ?float
    {
        return EngagementNumber::rate($this->withAppointment, $this->registered);
    }

    public function completionRate(): ?float
    {
        return EngagementNumber::rate($this->withCompletedAppointment, $this->withAppointment);
    }

    public function recurrenceRate(): ?float
    {
        return EngagementNumber::rate($this->withRecurrence, $this->withCompletedAppointment);
    }
}
