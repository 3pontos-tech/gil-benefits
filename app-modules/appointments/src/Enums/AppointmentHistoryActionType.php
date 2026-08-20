<?php

declare(strict_types=1);

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
    case NoShowMarked = 'no_show_marked';

    public function getIcon(): Heroicon
    {
        return match ($this) {
            self::ConsultantAssigned => Heroicon::UserPlus,
            self::ConsultantLeft => Heroicon::UserMinus,
            self::ConsultantChanged => Heroicon::ArrowsRightLeft,
            self::ReScheduled => Heroicon::Clock,
            self::NoShowMarked => Heroicon::UserMinus,
        };
    }

    public function getLabel(): string
    {
        return (string) __('appointments::enums.appointment_history_action_type.' . $this->value);
    }

    /**
     * @return array<int|string, string>
     */
    public function getColor(): array
    {
        return match ($this) {
            self::ConsultantAssigned => Color::Green,
            self::ConsultantLeft => Color::Red,
            self::ConsultantChanged => Color::Blue,
            self::ReScheduled => Color::Amber,
            self::NoShowMarked => Color::Purple,
        };
    }
}
