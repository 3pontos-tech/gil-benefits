<?php

declare(strict_types=1);

namespace TresPontosTech\Support\Enums;

use Filament\Support\Colors\Color;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Filament\Support\Icons\Heroicon;

enum TicketNotificationStatusEnum: string implements HasColor, HasIcon, HasLabel
{
    case Pending = 'pending';
    case Sent = 'sent';
    case Failed = 'failed';
    case Skipped = 'skipped';

    public function getLabel(): string
    {
        return __('support::enums.notification_status.' . $this->value);
    }

    public function getIcon(): Heroicon
    {
        return match ($this) {
            self::Pending => Heroicon::Clock,
            self::Sent => Heroicon::PaperAirplane,
            self::Failed => Heroicon::XCircle,
            self::Skipped => Heroicon::MinusCircle,
        };
    }

    public function getColor(): array|string
    {
        return match ($this) {
            self::Pending => Color::Amber,
            self::Sent => Color::Green,
            self::Failed => Color::Red,
            self::Skipped => Color::Gray,
        };
    }
}
