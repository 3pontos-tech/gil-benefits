<?php

declare(strict_types=1);

namespace TresPontosTech\IntegrationMonday\Observers;

use TresPontosTech\IntegrationMonday\Jobs\SyncMondayCardStatusJob;
use TresPontosTech\IntegrationMonday\Support\MondaySyncContext;
use TresPontosTech\Support\Models\SupportTicket;

/**
 * Outbound sync: when a ticket's status changes on the app side, mirror it onto
 * the Monday card. Skipped while muted (the change came from a Monday webhook).
 */
final class SupportTicketObserver
{
    public function updated(SupportTicket $ticket): void
    {
        if (MondaySyncContext::isMuted() || ! $ticket->wasChanged('status')) {
            return;
        }

        dispatch(new SyncMondayCardStatusJob($ticket->id, $ticket->status));
    }
}
