<?php

declare(strict_types=1);

namespace TresPontosTech\PanelAdmin\DTOs;

use TresPontosTech\PanelAdmin\Support\EngagementNumber;

/**
 * Engagement funnel of a single company: from contracted seats down to the
 * beneficiaries who came back for more than one consultancy.
 *
 * @phpstan-type FunnelRowArray array{
 *     company_id: string,
 *     company_name: string,
 *     seats: int,
 *     registered: int,
 *     registration_rate: float|null,
 *     with_appointment: int,
 *     scheduling_rate: float|null,
 *     with_completed: int,
 *     completion_rate: float|null,
 *     with_recurrence: int,
 *     recurrence_rate: float|null,
 * }
 */
final readonly class EngagementFunnelRow
{
    public function __construct(
        public string $companyId,
        public string $companyName,
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

    /**
     * Row shape consumed by the funnel table widget and the CSV export.
     *
     * @return FunnelRowArray
     */
    public function toArray(): array
    {
        return [
            'company_id' => $this->companyId,
            'company_name' => $this->companyName,
            'seats' => $this->seats,
            'registered' => $this->registered,
            'registration_rate' => $this->registrationRate(),
            'with_appointment' => $this->withAppointment,
            'scheduling_rate' => $this->schedulingRate(),
            'with_completed' => $this->withCompletedAppointment,
            'completion_rate' => $this->completionRate(),
            'with_recurrence' => $this->withRecurrence,
            'recurrence_rate' => $this->recurrenceRate(),
        ];
    }
}
