<?php

declare(strict_types=1);

namespace TresPontosTech\Appointments\DTO;

use Carbon\CarbonInterface;
use TresPontosTech\Appointments\Enums\AppointmentStatus;
use TresPontosTech\Appointments\Models\Appointment;

/**
 * O que a pessoa vê de um encontro, guardado antes de uma edição: depois do save, o modelo já não
 * sabe o que era. É o "antes" de AppointmentChange.
 */
final readonly class AppointmentSnapshot
{
    public function __construct(
        public AppointmentStatus $status,
        public ?string $consultantId,
        public CarbonInterface $appointmentAt,
        public ?string $meetingUrl,
    ) {}

    public static function of(Appointment $appointment): self
    {
        return new self(
            status: $appointment->status,
            consultantId: $appointment->consultant_id,
            appointmentAt: $appointment->appointment_at->copy(),
            meetingUrl: $appointment->meeting_url,
        );
    }
}
