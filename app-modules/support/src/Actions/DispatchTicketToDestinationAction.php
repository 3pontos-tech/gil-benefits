<?php

declare(strict_types=1);

namespace TresPontosTech\Support\Actions;

use TresPontosTech\Support\Contracts\TicketChannelSender;
use TresPontosTech\Support\Enums\TicketDestinationChannelEnum;
use TresPontosTech\Support\Enums\TicketDestinationStatusEnum;
use TresPontosTech\Support\Enums\TicketDestinationTypeEnum;
use TresPontosTech\Support\Models\SupportTicket;
use TresPontosTech\Support\Models\TicketDestination;

/**
 * Dispatches a ticket to a single destination: records the destination as
 * pending, resolves the sender for its type, sends, and persists the outcome.
 * The router orchestrates which (channel, type) pairs to dispatch to.
 */
final class DispatchTicketToDestinationAction
{
    public function execute(SupportTicket $ticket, TicketDestinationChannelEnum $channel, TicketDestinationTypeEnum $type): TicketDestination
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

        return $destination;
    }
}
