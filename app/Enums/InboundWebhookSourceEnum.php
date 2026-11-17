<?php

declare(strict_types=1);

namespace App\Enums;

use BackedEnum;
use Basement\Webhooks\Contracts\InboundWebhookContract;
use Filament\Support\Colors\Color;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Filament\Support\Icons\Heroicon;

enum InboundWebhookSourceEnum: string implements HasColor, HasIcon, HasLabel, InboundWebhookContract
{
    case Resend = 'resend';
    case Autentique = 'autentique';
    case Barte = 'barte';
    case Monday = 'monday';
    case Virtu = 'virtu';
    case Chatx = 'chatx';

    public function getLabel(): string
    {
        return match ($this) {
            self::Resend => 'Resend',
            self::Autentique => 'Autentique',
            self::Barte => 'Barte',
            self::Monday => 'Monday',
            self::Virtu => 'Virtu',
            self::Chatx => 'ChatX',
        };
    }

    /**
     * @return array<int, string>
     */
    public function getColor(): array
    {
        return match ($this) {
            self::Resend => Color::Teal,
            self::Autentique => Color::Blue,
            self::Barte => Color::Amber,
            self::Monday => Color::Violet,
            self::Virtu => Color::Emerald,
            self::Chatx => Color::Green,
        };
    }

    public function getIcon(): BackedEnum
    {
        return match ($this) {
            self::Resend => Heroicon::PaperAirplane,
            self::Autentique => Heroicon::DocumentText,
            self::Barte => Heroicon::CreditCard,
            self::Monday => Heroicon::ClipboardDocumentList,
            self::Virtu => Heroicon::Banknotes,
            self::Chatx => Heroicon::ChatBubbleLeftRight,
        };
    }
}
