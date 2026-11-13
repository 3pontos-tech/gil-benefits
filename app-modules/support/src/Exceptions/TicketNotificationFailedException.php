<?php

declare(strict_types=1);

namespace TresPontosTech\Support\Exceptions;

use RuntimeException;
use TresPontosTech\Support\Models\SupportTicketNotification;

final class TicketNotificationFailedException extends RuntimeException
{
    public static function for(SupportTicketNotification $notification): self
    {
        return new self(sprintf(
            'Ticket notification %s failed on %s (HTTP %s).',
            $notification->id,
            $notification->channel->value,
            $notification->response_code ?? '—',
        ));
    }
}
