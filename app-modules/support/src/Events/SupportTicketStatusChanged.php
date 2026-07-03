<?php

declare(strict_types=1);

namespace TresPontosTech\Support\Events;

use Illuminate\Foundation\Events\Dispatchable;
use TresPontosTech\Support\Enums\SupportTicketStatusEnum;
use TresPontosTech\Support\Models\SupportTicket;

/**
 * Fired when a ticket's status transitions. Domain-level and integration-agnostic:
 * the support module knows nothing about who listens (e.g. the Monday sync).
 *
 * Dispatched synchronously from TransitionSupportTicketStatusAction (no
 * after-commit) so synchronous listeners run within the originating call.
 */
final readonly class SupportTicketStatusChanged
{
    use Dispatchable;

    public function __construct(
        public SupportTicket $ticket,
        public SupportTicketStatusEnum $from,
        public SupportTicketStatusEnum $to,
    ) {}
}
