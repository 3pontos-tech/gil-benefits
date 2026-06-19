<?php

declare(strict_types=1);

namespace TresPontosTech\IntegrationMonday\Listeners;

use TresPontosTech\IntegrationMonday\Jobs\SyncMondayCardStatusJob;
use TresPontosTech\IntegrationMonday\Support\MondaySyncContext;
use TresPontosTech\Support\Events\SupportTicketStatusChanged;

/**
 * Outbound sync: mirrors an app-side ticket status change onto its Monday card.
 *
 * Runs synchronously (not queued) so the MondaySyncContext mute — set while a
 * Monday-originated change is being applied — is still active here and prevents
 * echoing that change back to the board. Only the actual push is queued.
 */
final class PushTicketStatusToMonday
{
    public function handle(SupportTicketStatusChanged $event): void
    {
        if (MondaySyncContext::isMuted()) {
            return;
        }

        dispatch(new SyncMondayCardStatusJob($event->ticket->id, $event->to));
    }
}
