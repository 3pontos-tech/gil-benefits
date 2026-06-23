<?php

declare(strict_types=1);

namespace TresPontosTech\Support\Events;

use Illuminate\Foundation\Events\Dispatchable;
use TresPontosTech\Support\Enums\SupportTicketStatusEnum;
use TresPontosTech\Support\Enums\TicketDestinationTypeEnum;

/**
 * An external destination (e.g. a Monday board) reports a new status for the
 * ticket behind one of its destinations. Integrations raise this without
 * touching support models; the support listener resolves the destination by its
 * reference and applies the guarded transition.
 */
final readonly class ExternalTicketStatusChanged
{
    use Dispatchable;

    public function __construct(
        public TicketDestinationTypeEnum $type,
        public string $reference,
        public SupportTicketStatusEnum $status,
    ) {}
}
