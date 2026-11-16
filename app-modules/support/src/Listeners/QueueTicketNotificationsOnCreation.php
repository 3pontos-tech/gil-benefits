<?php

declare(strict_types=1);

namespace TresPontosTech\Support\Listeners;

use TresPontosTech\Support\Actions\QueueTicketNotificationsAction;
use TresPontosTech\Support\Events\SupportTicketCreated;

final readonly class QueueTicketNotificationsOnCreation
{
    public function __construct(
        private QueueTicketNotificationsAction $notifications,
    ) {}

    public function handle(SupportTicketCreated $event): void
    {
        $this->notifications->execute($event->ticket, null, $event->ticket->status);
    }
}
