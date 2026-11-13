<?php

declare(strict_types=1);

namespace TresPontosTech\Support\Models\Builders;

use Illuminate\Database\Eloquent\Builder;
use TresPontosTech\Support\Enums\TicketNotificationStatusEnum;
use TresPontosTech\Support\Models\SupportTicketNotification;

/**
 * @extends Builder<SupportTicketNotification>
 */
class SupportTicketNotificationBuilder extends Builder
{
    public function pending(): self
    {
        return $this->where('status', TicketNotificationStatusEnum::Pending);
    }

    public function failPending(): int
    {
        return $this->pending()->update(['status' => TicketNotificationStatusEnum::Failed]);
    }
}
