<?php

declare(strict_types=1);

namespace TresPontosTech\IntegrationChatx\Actions;

use LogicException;
use TresPontosTech\IntegrationChatx\DTO\ChatxResolutionAnswerDTO;
use TresPontosTech\IntegrationChatx\Enums\ChatxEventEnum;
use TresPontosTech\IntegrationChatx\Exceptions\ChatxTicketConflictException;
use TresPontosTech\IntegrationChatx\Exceptions\ChatxTicketNotFoundException;
use TresPontosTech\Support\Enums\SupportTicketStatusEnum;
use TresPontosTech\Support\Enums\TicketOriginSourceEnum;
use TresPontosTech\Support\Events\OriginTicketStatusRequested;
use TresPontosTech\Support\Models\SupportTicket;

/**
 * This side only reads the ticket, to pick the answer the contract promises; the
 * status itself is changed by the support listener of OriginTicketStatusRequested.
 * The listener runs in the same request, and the answer is decided on the ticket as
 * it stands afterwards: if someone else moved it in between, the listener changed
 * nothing and the customer gets the 409 that status calls for, not a 200.
 *
 * An answer the ticket has already reached (reopen on an open ticket, confirm on a
 * closed one) is a 200 with nothing changed: ChatX retries deliveries it never saw
 * acknowledged, and a customer may tap the same button twice.
 */
final class AnswerResolutionFromChatx
{
    public function execute(ChatxResolutionAnswerDTO $dto): SupportTicket
    {
        $ticket = SupportTicket::query()->fromOrigin(TicketOriginSourceEnum::Chatx, $dto->externalReference)->first()
            ?? throw new ChatxTicketNotFoundException;

        if ($ticket->status === SupportTicketStatusEnum::Resolved) {
            event(new OriginTicketStatusRequested(
                TicketOriginSourceEnum::Chatx,
                $dto->externalReference,
                $this->requestedStatus($dto->event),
                $dto->message,
            ));

            $ticket->refresh();
        }

        $closed = $ticket->status === SupportTicketStatusEnum::Closed;

        return match ($dto->event) {
            ChatxEventEnum::ReopenRequested => $closed ? throw ChatxTicketConflictException::closed($ticket->status) : $ticket,
            ChatxEventEnum::ResolutionConfirmed => $closed ? $ticket : throw ChatxTicketConflictException::notResolved($ticket->status),
            ChatxEventEnum::TicketCreated => throw new LogicException('ticket.created is not an answer to a resolution.'),
        };
    }

    private function requestedStatus(ChatxEventEnum $event): SupportTicketStatusEnum
    {
        return match ($event) {
            ChatxEventEnum::ReopenRequested => SupportTicketStatusEnum::REOPENED_TO,
            ChatxEventEnum::ResolutionConfirmed => SupportTicketStatusEnum::Closed,
            ChatxEventEnum::TicketCreated => throw new LogicException('ticket.created is not an answer to a resolution.'),
        };
    }
}
