<?php

declare(strict_types=1);

namespace TresPontosTech\Billing\Core\Events\Credit;

use Illuminate\Foundation\Events\Dispatchable;

final class AppointmentCreditUsed
{
    use Dispatchable;

    public function __construct(
        public readonly string $appointmentId,
    ) {}
}
