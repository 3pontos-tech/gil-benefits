<?php

declare(strict_types=1);

namespace TresPontosTech\IntegrationGoogleCalendar\Exceptions;

use Exception;

class GoogleCalendarApiException extends Exception
{
    public function __construct(
        string $message = '',
        int $code = 0,
        public readonly bool $retryable = true,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, $code, $previous);
    }
}
