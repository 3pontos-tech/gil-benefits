<?php

declare(strict_types=1);

namespace TresPontosTech\Support\Events;

use Illuminate\Foundation\Events\Dispatchable;
use TresPontosTech\Support\Enums\SupportTicketStatusEnum;
use TresPontosTech\Support\Enums\TicketOriginSourceEnum;

/**
 * The requester, through the system the ticket was opened from, answers a resolved
 * ticket: reopen it or confirm it is solved. Integrations raise this without
 * touching support models; the support listener resolves the ticket by its origin
 * reference and applies the transition.
 */
final readonly class OriginTicketStatusRequested
{
    use Dispatchable;

    public function __construct(
        public TicketOriginSourceEnum $source,
        public string $reference,
        public SupportTicketStatusEnum $status,
        public ?string $message = null,
    ) {}
}
