<?php

declare(strict_types=1);

namespace TresPontosTech\IntegrationMonday\Listeners;

use Illuminate\Support\Facades\Log;
use TresPontosTech\IntegrationMonday\Events\MondayItemColumnChanged;
use TresPontosTech\IntegrationMonday\Support\MondayStatusMap;
use TresPontosTech\IntegrationMonday\Support\MondaySyncContext;
use TresPontosTech\Support\Actions\TransitionSupportTicketStatusAction;
use TresPontosTech\Support\Enums\SupportTicketStatusEnum;
use TresPontosTech\Support\Enums\TicketDestinationTypeEnum;
use TresPontosTech\Support\Exceptions\InvalidTransitionException;
use TresPontosTech\Support\Models\SupportTicket;
use TresPontosTech\Support\Models\TicketDestination;

/**
 * Inbound sync: turns a Monday status-column change into a guarded ticket
 * transition. Ignores other columns, unknown labels/items, and transitions the
 * support state machine forbids. Runs synchronously inside HandleMondayWebhookJob
 * so the MondaySyncContext mute spans the transition it triggers.
 */
final class SyncTicketStatusFromMonday
{
    public function __construct(
        private readonly TransitionSupportTicketStatusAction $transition,
    ) {}

    public function handle(MondayItemColumnChanged $event): void
    {
        if ($event->columnId !== (string) config('monday.columns.status') || $event->index === null) {
            return;
        }

        $status = MondayStatusMap::fromIndex($event->index);

        if (! $status instanceof SupportTicketStatusEnum) {
            return;
        }

        $destination = TicketDestination::query()
            ->where('type', TicketDestinationTypeEnum::Monday)
            ->where('reference_id', $event->itemId)
            ->with('ticket')
            ->first();

        $ticket = $destination?->ticket;

        // Unknown item, or already in the target state (e.g. echo of our own push).
        if ($ticket === null || $ticket->status === $status) {
            return;
        }

        try {
            // Mute the outbound listener: this change came from Monday, so it
            // must not be pushed straight back to the board.
            MondaySyncContext::mute(fn (): SupportTicket => $this->transition->execute($ticket, $status));
        } catch (InvalidTransitionException) {
            Log::info('Ignoring invalid Monday-driven ticket transition.', [
                'ticket_id' => $ticket->id,
                'from' => $ticket->status->value,
                'to' => $status->value,
            ]);
        }
    }
}
