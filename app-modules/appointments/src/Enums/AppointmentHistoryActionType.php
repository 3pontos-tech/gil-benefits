<?php

namespace TresPontosTech\Appointments\Enums;

use Filament\Support\Colors\Color;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Filament\Support\Icons\Heroicon;

enum AppointmentHistoryActionType: string implements HasColor, HasIcon, HasLabel
{
    case ConsultantAssigned = 'consultant_assigned';
    case ConsultantLeft = 'consultant_left';
    case ConsultantChanged = 'consultant_changed';
    case ReScheduled = 're_scheduled';

    public function getIcon(): Heroicon
    {
        return match ($this) {
            self::ConsultantAssigned => Heroicon::ArrowUpCircle,
            self::ConsultantLeft => Heroicon::ArrowDownLeft,
            self::ConsultantChanged => Heroicon::UserPlus,
            self::ReScheduled => Heroicon::Clock,
        };
    }

    public function getLabel(): string
    {
        return $this->name;
    }

    public function getColor(): array
    {
        return match ($this) {
            self::ConsultantAssigned => Color::Blue,
            self::ConsultantLeft => Color::Red,
            self::ConsultantChanged => Color::Green,
            self::ReScheduled => Color::Amber,
        };
    }
}
