<?php

declare(strict_types=1);

namespace TresPontosTech\Appointments\Events;

use Illuminate\Foundation\Events\Dispatchable;
use TresPontosTech\Appointments\Models\Appointment;

/**
 * A slot was reserved for the employee. Dispatched inside the booking transaction so a
 * listener that pays for it (credits) runs, or fails, together with the reservation.
 */
final readonly class AppointmentReserved
{
    use Dispatchable;

    public function __construct(
        public Appointment $appointment,
        public bool $paidWithQuota,
    ) {}
}
