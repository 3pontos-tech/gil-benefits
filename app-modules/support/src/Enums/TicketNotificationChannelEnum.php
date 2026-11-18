<?php

declare(strict_types=1);

namespace TresPontosTech\Support\Enums;

use Filament\Support\Contracts\HasLabel;

enum TicketNotificationChannelEnum: string implements HasLabel
{
    case Whatsapp = 'whatsapp';

    public function getLabel(): string
    {
        return __('support::enums.notification_channel.' . $this->value);
    }
}
