<?php

declare(strict_types=1);

namespace TresPontosTech\Support\Actions;

use TresPontosTech\Support\Enums\TicketDestinationChannelEnum;
use TresPontosTech\Support\Enums\TicketDestinationStatusEnum;
use TresPontosTech\Support\Enums\TicketDestinationTypeEnum;
use TresPontosTech\Support\Models\SupportTicket;
use TresPontosTech\Support\Models\TicketDestination;
use TresPontosTech\Support\TicketSenderManager;

/**
 * Dispatches a ticket to a single destination: records the destination as
 * pending, resolves the sender for its type, sends, and persists the outcome.
 * The router orchestrates which (channel, type) pairs to dispatch to.
 */
final readonly class DispatchTicketToDestinationAction
{
    public function __construct(private TicketSenderManager $senders) {}

    public function execute(SupportTicket $ticket, TicketDestinationChannelEnum $channel, TicketDestinationTypeEnum $type): TicketDestination
    {
        $destination = TicketDestination::query()->create([
            'support_ticket_id' => $ticket->id,
            'type' => $type,
            'channel' => $channel,
            'status' => TicketDestinationStatusEnum::Pending,
        ]);

        $destination->update($this->senders->getSender($type)->send($ticket, $channel)->jsonSerialize());

        return $destination;
    }
}
