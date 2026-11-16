<?php

declare(strict_types=1);

namespace TresPontosTech\Support\Enums;

use Filament\Support\Contracts\HasLabel;

enum TicketNotificationEventEnum: string implements HasLabel
{
    case Created = 'created';
    case StatusChanged = 'status_changed';

    public function getLabel(): string
    {
        return __('support::enums.notification_event.' . $this->value);
    }
}
