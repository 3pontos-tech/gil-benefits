<?php

declare(strict_types=1);

namespace TresPontosTech\Support\Contracts;

use TresPontosTech\Support\DTOs\DispatchResult;
use TresPontosTech\Support\Enums\TicketDestinationChannelEnum;
use TresPontosTech\Support\Models\SupportTicket;

/**
 * Sends a ticket to a destination of a given type (email, monday, ...). One
 * implementation per type. Sending only — no persistence, no ticket mutation.
 */
interface TicketChannelSender
{
    public function send(SupportTicket $ticket, TicketDestinationChannelEnum $channel): DispatchResult;
}
