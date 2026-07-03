<?php

declare(strict_types=1);

namespace TresPontosTech\IntegrationMonday\Listeners;

use TresPontosTech\IntegrationMonday\Jobs\SyncMondayCardStatusJob;
use TresPontosTech\Support\Events\SupportTicketStatusChanged;

/**
 * Outbound sync: mirrors a ticket status change onto its Monday card.
 *
 * Runs for every status change, including those that originated on Monday. The
 * echo is harmless: re-pushing the same status is idempotent (and bumps the
 * card's "last updated" date), and the resulting webhook is dropped because the
 * ticket is already in that state.
 */
final class PushTicketStatusToMonday
{
    public function handle(SupportTicketStatusChanged $event): void
    {
        dispatch(new SyncMondayCardStatusJob($event->ticket->id, $event->to));
    }
}
