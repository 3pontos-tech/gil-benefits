<?php

declare(strict_types=1);

namespace TresPontosTech\Support\Services;

use TresPontosTech\Support\Contracts\TicketChannelSender;
use TresPontosTech\Support\Enums\SupportTicketStatusEnum;
use TresPontosTech\Support\Enums\TicketDestinationStatusEnum;
use TresPontosTech\Support\Models\SupportTicket;
use TresPontosTech\Support\Models\TicketDestination;

/**
 * Orchestrates routing only. For each destination channel it creates the
 * TicketDestination, delegates the actual send to the resolved channel sender,
 * persists the outcome, and finally derives the ticket status. It holds no
 * per-type sending logic and no requester confirmation (queued at creation).
 */
final class TicketRouterService
{
    public function dispatch(SupportTicket $ticket): void
    {
        $statuses = [];

        foreach ($ticket->category->destinationChannels() as $channel) {
            $type = $channel->getDestinationType();

            $destination = TicketDestination::query()->create([
                'support_ticket_id' => $ticket->id,
                'type' => $type,
                'channel' => $channel,
                'status' => TicketDestinationStatusEnum::Pending,
            ]);

            /** @var TicketChannelSender $sender */
            $sender = resolve($type->senderClass());

            $result = $sender->send($ticket, $channel);

            $destination->update($result->jsonSerialize());

            $statuses[] = $result->status;
        }

        $ticket->update([
            'status' => SupportTicketStatusEnum::fromDestinations($statuses),
        ]);
    }
}
