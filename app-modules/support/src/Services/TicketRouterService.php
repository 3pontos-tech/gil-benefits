<?php

declare(strict_types=1);

namespace TresPontosTech\Support\Services;

use TresPontosTech\Support\Contracts\TicketChannelSender;
use TresPontosTech\Support\Enums\TicketDestinationChannelEnum;
use TresPontosTech\Support\Enums\TicketDestinationStatusEnum;
use TresPontosTech\Support\Enums\TicketDestinationTypeEnum;
use TresPontosTech\Support\Models\SupportTicket;
use TresPontosTech\Support\Models\TicketDestination;

/**
 * Orchestrates routing only. For each sector channel, and each delivery type of
 * that channel, it creates the TicketDestination, delegates the send to the
 * resolved channel sender, and persists the outcome. Notifying a sector does not
 * advance the ticket's lifecycle — it stays Pending until an agent picks it up.
 */
final class TicketRouterService
{
    public function dispatch(SupportTicket $ticket): void
    {
        foreach ($ticket->category->destinationChannels() as $channel) {
            foreach ($channel->getDestinationTypes() as $type) {
                $this->dispatchTo($ticket, $channel, $type);
            }
        }
    }

    private function dispatchTo(SupportTicket $ticket, TicketDestinationChannelEnum $channel, TicketDestinationTypeEnum $type): void
    {
        $destination = TicketDestination::query()->create([
            'support_ticket_id' => $ticket->id,
            'type' => $type,
            'channel' => $channel,
            'status' => TicketDestinationStatusEnum::Pending,
        ]);

        /** @var TicketChannelSender $sender */
        $sender = resolve($type->senderClass());

        $destination->update($sender->send($ticket, $channel)->jsonSerialize());
    }
}
