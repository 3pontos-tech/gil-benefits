<?php

declare(strict_types=1);

namespace TresPontosTech\Support\Services;

use TresPontosTech\Support\Actions\DispatchTicketToDestinationAction;
use TresPontosTech\Support\Models\SupportTicket;

/**
 * Orchestrates routing only: for each sector channel, and each delivery type of
 * that channel, it delegates the actual dispatch to a single-destination action.
 * Notifying a sector does not advance the ticket's lifecycle — it stays Pending
 * until an agent picks it up.
 */
final class TicketRouterService
{
    public function __construct(
        private readonly DispatchTicketToDestinationAction $dispatchToDestination,
    ) {}

    public function dispatch(SupportTicket $ticket): void
    {
        foreach ($ticket->category->destinationChannels() as $channel) {
            foreach ($channel->getDestinationTypes() as $type) {
                $this->dispatchToDestination->execute($ticket, $channel, $type);
            }
        }
    }
}
