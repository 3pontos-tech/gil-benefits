<?php

declare(strict_types=1);

namespace TresPontosTech\Support\Exceptions;

use RuntimeException;
use TresPontosTech\Support\Enums\SupportTicketStatusEnum;

final class InvalidTransitionException extends RuntimeException
{
    public static function between(SupportTicketStatusEnum $from, SupportTicketStatusEnum $to): self
    {
        return new self(sprintf(
            'Cannot transition a support ticket from "%s" to "%s".',
            $from->value,
            $to->value,
        ));
    }

    public static function moved(SupportTicketStatusEnum $expected, SupportTicketStatusEnum $actual): self
    {
        return new self(sprintf(
            'The support ticket is no longer "%s"; it is "%s".',
            $expected->value,
            $actual->value,
        ));
    }
}
