<?php

declare(strict_types=1);

namespace TresPontosTech\Support\Enums;

use Filament\Support\Contracts\HasLabel;
use Illuminate\Contracts\Support\Htmlable;
use TresPontosTech\Support\Contracts\TicketChannelSender;
use TresPontosTech\Support\Senders\EmailTicketSender;
use TresPontosTech\Support\Senders\MondayTicketSender;

enum TicketDestinationTypeEnum: string implements HasLabel
{
    case Monday = 'monday';
    case Email = 'email';

    public function getLabel(): string|Htmlable|null
    {
        return __('support::enums.destination_type.' . $this->value);
    }

    /**
     * The sender bound to this type. A new type = new case + new handler class;
     * the orchestrator never changes.
     *
     * @return class-string<TicketChannelSender>
     */
    public function senderClass(): string
    {
        return match ($this) {
            self::Email => EmailTicketSender::class,
            self::Monday => MondayTicketSender::class,
        };
    }
}
