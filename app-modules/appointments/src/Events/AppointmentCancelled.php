<?php

declare(strict_types=1);

namespace TresPontosTech\Appointments\Events;

use Illuminate\Foundation\Events\Dispatchable;
use TresPontosTech\Appointments\Models\Appointment;

final readonly class AppointmentCancelled
{
    use Dispatchable;

    public function __construct(
        public Appointment $appointment,
    ) {}
}
