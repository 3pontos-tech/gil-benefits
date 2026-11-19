<?php

declare(strict_types=1);

namespace TresPontosTech\Support;

use Illuminate\Support\Manager;
use TresPontosTech\Support\Contracts\TicketNotificationChannel;
use TresPontosTech\Support\Enums\TicketNotificationChannelEnum;

/**
 * One notifier per channel. The support module ships none; an integration module
 * plugs its channel in with extend(), keyed by the enum value.
 */
class TicketNotificationManager extends Manager
{
    public function getDefaultDriver(): string
    {
        return TicketNotificationChannelEnum::Whatsapp->value;
    }

    public function channel(TicketNotificationChannelEnum $channel): TicketNotificationChannel
    {
        return $this->driver($channel->value);
    }

    /**
     * @return list<TicketNotificationChannelEnum>
     */
    public function registeredChannels(): array
    {
        return array_values(array_filter(
            TicketNotificationChannelEnum::cases(),
            fn (TicketNotificationChannelEnum $channel): bool => isset($this->customCreators[$channel->value]),
        ));
    }
}
