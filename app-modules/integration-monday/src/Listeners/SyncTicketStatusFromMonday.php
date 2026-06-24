<?php

declare(strict_types=1);

namespace TresPontosTech\IntegrationMonday\Listeners;

use TresPontosTech\IntegrationMonday\Events\MondayItemColumnChanged;
use TresPontosTech\IntegrationMonday\Support\MondayStatusMap;
use TresPontosTech\Support\Enums\SupportTicketStatusEnum;
use TresPontosTech\Support\Enums\TicketDestinationTypeEnum;
use TresPontosTech\Support\Events\ExternalTicketStatusChanged;

/**
 * Inbound sync: translates a Monday status-column change into a support domain
 * event. It maps the (stable) label index to the support status and reports it
 * by destination reference — it never touches support models or the transition
 * action; the support module owns reconciling the ticket.
 */
final class SyncTicketStatusFromMonday
{
    public function handle(MondayItemColumnChanged $event): void
    {
        if ($event->columnId !== (string) config('monday.columns.status') || $event->index === null) {
            return;
        }

        $status = MondayStatusMap::fromIndex($event->index);

        if (! $status instanceof SupportTicketStatusEnum) {
            return;
        }

        event(new ExternalTicketStatusChanged(
            type: TicketDestinationTypeEnum::Monday,
            reference: $event->itemId,
            status: $status,
        ));
    }
}
