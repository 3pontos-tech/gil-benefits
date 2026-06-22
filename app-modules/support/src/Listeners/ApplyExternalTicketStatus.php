<?php

declare(strict_types=1);

namespace TresPontosTech\Support\Listeners;

use Illuminate\Support\Facades\Log;
use TresPontosTech\Support\Actions\TransitionSupportTicketStatusAction;
use TresPontosTech\Support\Events\ExternalTicketStatusChanged;
use TresPontosTech\Support\Exceptions\InvalidTransitionException;
use TresPontosTech\Support\Models\TicketDestination;

/**
 * Applies a status reported by an external destination. Resolves the ticket from
 * the destination reference, then runs the guarded transition — ignoring no-ops
 * (already in the target state) and transitions the state machine forbids.
 */
final class ApplyExternalTicketStatus
{
    public function __construct(
        private readonly TransitionSupportTicketStatusAction $transition,
    ) {}

    public function handle(ExternalTicketStatusChanged $event): void
    {
        $ticket = TicketDestination::query()
            ->where('type', $event->type)
            ->where('reference_id', $event->reference)
            ->first()?->ticket;

        if ($ticket === null || $ticket->status === $event->status) {
            return;
        }

        try {
            $this->transition->execute($ticket, $event->status);
        } catch (InvalidTransitionException) {
            Log::info('Ignoring forbidden externally-reported ticket transition.', [
                'ticket_id' => $ticket->id,
                'from' => $ticket->status->value,
                'to' => $event->status->value,
            ]);
        }
    }
}
