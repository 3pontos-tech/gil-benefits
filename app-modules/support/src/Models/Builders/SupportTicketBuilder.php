<?php

declare(strict_types=1);

namespace TresPontosTech\Support\Models\Builders;

use Illuminate\Database\Eloquent\Builder;
use TresPontosTech\Support\Enums\SupportTicketStatusEnum;
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
}
