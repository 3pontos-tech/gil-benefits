<?php

declare(strict_types=1);

namespace TresPontosTech\Support\Listeners;

use Illuminate\Support\Facades\Log;
use TresPontosTech\Support\Actions\TransitionSupportTicketStatusAction;
use TresPontosTech\Support\Enums\SupportTicketStatusEnum;
use TresPontosTech\Support\Events\OriginTicketStatusRequested;
use TresPontosTech\Support\Exceptions\InvalidTransitionException;
use TresPontosTech\Support\Models\SupportTicket;

/**
 * Applies the requester's answer to a resolved ticket. Only a ticket still resolved
 * changes: the answer refers to the resolution, so once someone else has moved the
 * ticket (an agent reopened or closed it) the answer is stale and a repeat delivery
 * changes nothing, which keeps the history and the e-mails from doubling.
 *
 * The requester is the actor even when their account was deleted since: the answer
 * is still theirs, and the history should say so.
 */
final class ApplyOriginTicketStatus
{
    public function __construct(
        private readonly TransitionSupportTicketStatusAction $transition,
    ) {}

    public function handle(OriginTicketStatusRequested $event): void
    {
        $ticket = SupportTicket::query()->fromOrigin($event->source, $event->reference)->first();

        if (! $ticket instanceof SupportTicket || $ticket->status !== SupportTicketStatusEnum::Resolved) {
            return;
        }

        try {
            $this->transition->execute(
                $ticket,
                $event->status,
                $event->source->statusChangeSource(),
                $ticket->user()->withTrashed()->first(),
                $ticket->status->isReopenedBy($event->status) ? $event->message : null,
            );
        } catch (InvalidTransitionException) {
            Log::info('Ignoring forbidden origin-requested ticket transition.', [
                'ticket_id' => $ticket->id,
                'from' => $ticket->status->value,
                'to' => $event->status->value,
            ]);
        }
    }
}
