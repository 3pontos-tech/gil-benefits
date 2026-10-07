<?php

declare(strict_types=1);

namespace TresPontosTech\Appointments\Exceptions;

use RuntimeException;

class AppointmentFeedbackException extends RuntimeException
{
    public static function requiresCompletion(): self
    {
        return new self(__('appointments::resources.appointments.exceptions.feedback_requires_completion'));
    }

    public static function alreadyGiven(): self
    {
        return new self(__('appointments::resources.appointments.exceptions.feedback_already_given'));
    }

    public static function invalidRating(): self
    {
        return new self(__('appointments::resources.appointments.exceptions.invalid_rating'));
    }
}
