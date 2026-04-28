<?php

namespace TresPontosTech\Appointments\Enums;

use Filament\Support\Colors\Color;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use TresPontosTech\Appointments\Actions\Transitions\AbstractAppointmentTransition;
use TresPontosTech\Appointments\Actions\Transitions\ActiveTransition;
use TresPontosTech\Appointments\Actions\Transitions\CancelledLateTransition;
use TresPontosTech\Appointments\Actions\Transitions\CancelledTransition;
use TresPontosTech\Appointments\Actions\Transitions\CompletedTransition;
use TresPontosTech\Appointments\Actions\Transitions\PendingTransition;
use TresPontosTech\Appointments\Models\Appointment;

enum AppointmentStatus: string implements HasColor, HasIcon, HasLabel
{
    case Pending = 'pending';

    case Active = 'active';

    case Completed = 'completed';

    case Cancelled = 'cancelled';

    case CancelledLate = 'cancelled_late';

    public function getIcon(): Heroicon
    {
        return match ($this) {
            self::Pending => Heroicon::Clock,
            self::Active => Heroicon::Check,
            self::Completed => Heroicon::CheckCircle,
            self::Cancelled => Heroicon::XCircle,
            self::CancelledLate => Heroicon::XMark,
        };
    }

    public function getColor(): array
    {
        return match ($this) {
            self::Pending => Color::Amber,
            self::Active => Color::Blue,
            self::Completed => Color::Green,
            self::Cancelled => Color::Red,
            self::CancelledLate => Color::Orange,
        };
    }

    public function getLabel(): string|Htmlable|null
    {
        return __(
            'appointments::enums.appointment_status.' . $this->value
        );
    }

    public function transition(Appointment $appointment): AbstractAppointmentTransition
    {
        return match ($this) {
            self::Pending => new PendingTransition($appointment),
            self::Active => new ActiveTransition($appointment),
            self::Completed => new CompletedTransition($appointment),
            self::Cancelled => new CancelledTransition($appointment),
            self::CancelledLate => new CancelledLateTransition($appointment),
        };
    }

    public static function resolveCancellationStatus(Appointment $appointment, CancellationActor $actor): self
    {
        if ($actor !== CancellationActor::User) {
            return self::Cancelled;
        }

        $hoursUntil = now()->diffInHours($appointment->appointment_at, absolute: false);

        return $hoursUntil >= 24 ? self::Cancelled : self::CancelledLate;
    }
}
