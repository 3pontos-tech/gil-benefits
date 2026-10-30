<?php

declare(strict_types=1);

namespace TresPontosTech\Support;

use Illuminate\Support\Manager;
use TresPontosTech\Support\Contracts\TicketChannelSender;
use TresPontosTech\Support\Enums\TicketDestinationTypeEnum;
use TresPontosTech\Support\Senders\EmailTicketSender;

/**
 * One sender per destination type. The support module ships e-mail; an integration
 * module plugs its own type in with extend(), keyed by the enum value.
 */
class TicketSenderManager extends Manager
{
    public function getDefaultDriver(): string
    {
        return TicketDestinationTypeEnum::Email->value;
    }

    public function createEmailDriver(): TicketChannelSender
    {
        return new EmailTicketSender;
    }

    public function getSender(TicketDestinationTypeEnum $type): TicketChannelSender
    {
        return $this->driver($type->value);
    }
}
