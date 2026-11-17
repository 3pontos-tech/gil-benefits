<?php

declare(strict_types=1);

namespace TresPontosTech\Support\Models\Builders;

use Illuminate\Database\Eloquent\Builder;
use TresPontosTech\Support\Enums\SupportTicketStatusEnum;
use TresPontosTech\Support\Enums\TicketOriginSourceEnum;
use TresPontosTech\Support\Models\SupportTicket;

/**
 * @extends Builder<SupportTicket>
 */
class SupportTicketBuilder extends Builder
{
    /**
     * Read from the status history, so tickets reopened before the history existed
     * are not found.
     */
    public function reopened(): self
    {
        return $this->whereHas('statusChanges', fn (Builder $changes) => $changes
            ->where('from_status', SupportTicketStatusEnum::REOPENED_FROM)
            ->where('to_status', SupportTicketStatusEnum::REOPENED_TO));
    }

    /**
     * Drops the global scopes: an integration looks tickets up with no Filament
     * tenant to satisfy the company scope, and scoped it would find nothing.
     */
    public function fromOrigin(TicketOriginSourceEnum $source, string $reference): self
    {
        return $this->withoutGlobalScopes()
            ->whereHas('origin', fn (Builder $origin) => $origin
                ->where('source', $source)
                ->where('external_reference', $reference));
    }
}
