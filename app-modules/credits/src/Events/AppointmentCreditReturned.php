<?php

declare(strict_types=1);

namespace TresPontosTech\Credits\Events;

use Illuminate\Foundation\Events\Dispatchable;

final class AppointmentCreditReturned
{
    use Dispatchable;

    public function __construct(
        public readonly string $appointmentId,
    ) {}
}
