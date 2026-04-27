<?php

namespace TresPontosTech\Appointments\Enums;

use BadMethodCallException;
use Filament\Support\Colors\Color;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use TresPontosTech\Appointments\Actions\StateMachine\AbstractAppointmentStep;
use TresPontosTech\Appointments\Actions\StateMachine\AppointmentActiveStep;
use TresPontosTech\Appointments\Actions\StateMachine\AppointmentPendingStep;
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

    /** @return list<self> */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Pending => [self::Active, self::Cancelled, self::CancelledLate],
            self::Active => [self::Completed, self::Cancelled, self::CancelledLate],
            self::Completed, self::Cancelled, self::CancelledLate => [],
        };
    }

    /** @return list<self> */
    public static function creditConsuming(): array
    {
        return [self::CancelledLate];
    }

    public function canTransitionTo(self $target): bool
    {
        return in_array($target, $this->allowedTransitions(), true);
    }

    public function currentStep(Appointment $appointment): AbstractAppointmentStep
    {
        return match ($this) {
            self::Pending => new AppointmentPendingStep($appointment),
            self::Active => new AppointmentActiveStep($appointment),
            self::Completed, self::Cancelled, self::CancelledLate => throw new BadMethodCallException(
                sprintf('Status "%s" is terminal and has no associated step.', $this->value)
            ),
        };
    }
}
