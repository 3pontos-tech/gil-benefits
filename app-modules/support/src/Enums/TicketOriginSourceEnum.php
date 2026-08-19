<?php

declare(strict_types=1);

namespace TresPontosTech\Support\Enums;

use Filament\Support\Colors\Color;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Filament\Support\Icons\Heroicon;

/**
 * External system a ticket was opened from. Only tickets that came in through an
 * integration carry an origin — the ones opened on the platform (panel or public
 * help center) have none, which is why TicketOrigin is a separate row and not a
 * column on every ticket.
 */
enum TicketOriginSourceEnum: string implements HasColor, HasIcon, HasLabel
{
    case Chatx = 'chatx';

    public function getLabel(): string
    {
        return __('support::enums.origin_source.' . $this->value);
    }

    public function getIcon(): Heroicon
    {
        return match ($this) {
            self::Chatx => Heroicon::ChatBubbleLeftRight,
        };
    }

    public function getColor(): array|string
    {
        return match ($this) {
            self::Chatx => Color::Emerald,
        };
    }
}
