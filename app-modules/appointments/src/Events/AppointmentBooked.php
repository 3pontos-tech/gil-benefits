<?php

declare(strict_types=1);

namespace TresPontosTech\Appointments\Events;

use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use TresPontosTech\Appointments\Models\Appointment;

final readonly class AppointmentBooked implements ShouldDispatchAfterCommit
{
    use Dispatchable;

    public function __construct(
        public Appointment $appointment,
    ) {}
}
