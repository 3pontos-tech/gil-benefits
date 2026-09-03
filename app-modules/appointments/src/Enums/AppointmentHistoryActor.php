<?php

declare(strict_types=1);

namespace TresPontosTech\Appointments\Enums;

use Filament\Support\Colors\Color;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Filament\Support\Icons\Heroicon;

/**
 * Which side of the product performed a history entry.
 *
 * Kept apart from CancellationActor on purpose: that one also answers for the system
 * (automatic cancellations) and drives billing, while a history entry is always written by
 * someone pressing a button. Same words, different questions.
 */
enum AppointmentHistoryActor: string implements HasColor, HasIcon, HasLabel
{
    case Admin = 'admin';
    case User = 'user';
    case Consultant = 'consultant';

    public function getLabel(): string
    {
        return (string) __('appointments::enums.appointment_history_actor.' . $this->value);
    }

    public function getIcon(): Heroicon
    {
        return match ($this) {
            self::Admin => Heroicon::ShieldCheck,
            self::User => Heroicon::User,
            self::Consultant => Heroicon::AcademicCap,
        };
    }

    /**
     * @return array<int|string, string>
     */
    public function getColor(): array
    {
        return match ($this) {
            self::Admin => Color::Indigo,
            self::User => Color::Gray,
            self::Consultant => Color::Purple,
        };
    }
}
