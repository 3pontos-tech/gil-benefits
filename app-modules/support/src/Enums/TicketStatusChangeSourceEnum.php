<?php

declare(strict_types=1);

namespace TresPontosTech\Support\Enums;

use Filament\Support\Colors\Color;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/**
 * Who changed the ticket status. Not the opening channel: a ticket opened in the
 * app can later be moved by an admin or by an external destination.
 */
enum TicketStatusChangeSourceEnum: string implements HasColor, HasLabel
{
    case Admin = 'admin';
    case Requester = 'requester';
    case ExternalDestination = 'external_destination';
    case Chatx = 'chatx';

    public function getLabel(): string
    {
        return __('support::enums.status_change_source.' . $this->value);
    }

    public function getColor(): array|string
    {
        return match ($this) {
            self::Admin => Color::Indigo,
            self::Requester => Color::Sky,
            self::ExternalDestination => Color::Amber,
            self::Chatx => Color::Emerald,
        };
    }
}
