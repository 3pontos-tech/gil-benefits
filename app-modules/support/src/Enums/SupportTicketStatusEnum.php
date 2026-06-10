<?php

declare(strict_types=1);

namespace TresPontosTech\Support\Enums;

use Filament\Support\Colors\Color;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;

enum SupportTicketStatusEnum: string implements HasColor, HasIcon, HasLabel
{
    case Pending = 'pending';
    case Dispatched = 'dispatched';
    case Failed = 'failed';
    case Resolved = 'resolved';
    case Closed = 'closed';

    public function getLabel(): string|Htmlable|null
    {
        return __('support::enums.ticket_status.' . $this->value);
    }

    public function getIcon(): Heroicon
    {
        return match ($this) {
            self::Pending => Heroicon::Clock,
            self::Dispatched => Heroicon::PaperAirplane,
            self::Failed => Heroicon::XCircle,
            self::Resolved => Heroicon::CheckCircle,
            self::Closed => Heroicon::ArchiveBox,
        };
    }

    public function getColor(): array|string
    {
        return match ($this) {
            self::Pending => Color::Amber,
            self::Dispatched => Color::Blue,
            self::Failed => Color::Red,
            self::Resolved => Color::Green,
            self::Closed => Color::Gray,
        };
    }
}
