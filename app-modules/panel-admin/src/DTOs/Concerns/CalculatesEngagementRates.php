<?php

declare(strict_types=1);

namespace TresPontosTech\PanelAdmin\DTOs\Concerns;

use TresPontosTech\PanelAdmin\Support\EngagementNumber;

/**
 * Conversion rates between adjacent funnel steps, defined once so the
 * consolidated card and the per-company row can never disagree on a denominator.
 *
 * @property-read int $seats
 * @property-read int $registered
 * @property-read int $withAppointment
 * @property-read int $withCompletedAppointment
 * @property-read int $withRecurrence
 */
trait CalculatesEngagementRates
{
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
