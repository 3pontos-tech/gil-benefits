<?php

declare(strict_types=1);

namespace TresPontosTech\Appointments\Exceptions;

use RuntimeException;

class AppointmentStateException extends RuntimeException
{
    public static function cannotReschedule(): self
    {
        return new self(__('appointments::resources.appointments.exceptions.cannot_reschedule'));
    }

    public static function cannotCancel(): self
    {
        return new self(__('appointments::resources.appointments.exceptions.cannot_cancel'));
    }
}
