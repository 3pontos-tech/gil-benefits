<?php

declare(strict_types=1);

namespace TresPontosTech\Support\Events;

use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use TresPontosTech\Support\Models\SupportTicket;

final readonly class SupportTicketCreated implements ShouldDispatchAfterCommit
{
    use Dispatchable;

    public function __construct(
        public SupportTicket $ticket,
    ) {}
}
