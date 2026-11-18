<?php

declare(strict_types=1);

namespace TresPontosTech\Support\Listeners;

use TresPontosTech\Support\Actions\QueueTicketNotificationsAction;
use TresPontosTech\Support\Events\SupportTicketStatusChanged;

final readonly class QueueTicketNotificationsOnStatusChange
{
    public function __construct(
        private QueueTicketNotificationsAction $notifications,
    ) {}

    public function handle(SupportTicketStatusChanged $event): void
    {
        $this->notifications->execute($event->ticket, $event->from, $event->to);
    }
}
