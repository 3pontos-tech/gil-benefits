<?php

declare(strict_types=1);

namespace TresPontosTech\Support\Senders;

use TresPontosTech\Support\Contracts\TicketChannelSender;
use TresPontosTech\Support\DTOs\DispatchResult;
use TresPontosTech\Support\Enums\TicketDestinationChannelEnum;
use TresPontosTech\Support\Models\SupportTicket;

final class MondayTicketSender implements TicketChannelSender
{
    public function send(SupportTicket $ticket, TicketDestinationChannelEnum $channel): DispatchResult
    {
        // Phase 2 — Monday integration not implemented yet. No-op, stays pending.
        return DispatchResult::pending();
    }
}
