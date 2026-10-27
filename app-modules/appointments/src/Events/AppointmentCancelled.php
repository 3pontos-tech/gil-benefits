<?php

declare(strict_types=1);

namespace TresPontosTech\Appointments\Events;

use Illuminate\Foundation\Events\Dispatchable;
use TresPontosTech\Appointments\Models\Appointment;

/**
 * Dispatched synchronously, inside the cancellation transaction, on purpose: the credit
 * listener settles money and must commit or roll back with the status change. Do not
 * add ShouldDispatchAfterCommit here; listeners that need the commit declare
 * ShouldHandleEventsAfterCommit themselves.
 */
final readonly class AppointmentCancelled
{
    use Dispatchable;

    public function __construct(
        public Appointment $appointment,
    ) {}
}
