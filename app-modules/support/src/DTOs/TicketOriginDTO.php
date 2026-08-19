<?php

declare(strict_types=1);

namespace TresPontosTech\Support\DTOs;

use TresPontosTech\Support\Enums\TicketOriginSourceEnum;

/**
 * Where an incoming ticket came from. Source and reference are meaningless apart,
 * so they travel as one value — a ticket either has a full origin or none at all.
 */
final readonly class TicketOriginDTO
{
    public function __construct(
        public TicketOriginSourceEnum $source,
        public string $externalReference,
    ) {}
}
