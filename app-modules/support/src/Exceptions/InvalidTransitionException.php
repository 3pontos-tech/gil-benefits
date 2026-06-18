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
}
