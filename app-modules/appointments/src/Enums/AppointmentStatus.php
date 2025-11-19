<?php

namespace TresPontosTech\Appointments\Enums;

use Filament\Support\Colors\Color;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use TresPontosTech\Appointments\Actions\StateMachine\AbstractAppointmentStep;
use TresPontosTech\Appointments\Actions\StateMachine\AppointmentActiveStep;
use TresPontosTech\Appointments\Actions\StateMachine\AppointmentDoneStep;
use TresPontosTech\Appointments\Actions\StateMachine\AppointmentDraftStep;
use TresPontosTech\Appointments\Actions\StateMachine\AppointmentPendingStep;
use TresPontosTech\Appointments\Actions\StateMachine\AppointmentSchedulingStep;
use TresPontosTech\Appointments\Models\Appointment;

enum AppointmentStatus: string implements HasColor, HasIcon, HasLabel
{
    case Draft = 'draft';

    case Pending = 'pending';

    case Scheduling = 'scheduling';
    case Active = 'active';
    case Completed = 'completed';

    case Cancelled = 'cancelled';

    public function getIcon(): Heroicon
    {
        return match ($this) {
            self::Draft => Heroicon::Pencil,
            self::Pending => Heroicon::Clock,
            self::Scheduling => Heroicon::Calendar,
            self::Active => Heroicon::Check,
            self::Completed => Heroicon::CheckCircle,
            self::Cancelled => Heroicon::XCircle,
        };
    }

    public function getColor(): array
    {
        return match ($this) {
            self::Draft => Color::Gray,
            self::Pending => Color::Amber,
            self::Scheduling => Color::Yellow,
            self::Active => Color::Blue,
            self::Completed => Color::Green,
            self::Cancelled => Color::Red,
        };
    }

    public function getLabel(): string|Htmlable|null
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Pending => 'Pending',
            self::Scheduling => 'Scheduling',
            self::Active => 'Scheduled',
            self::Completed => 'Completed',
            self::Cancelled => 'Cancelled',
        };
    }

    public function currentStep(Appointment $appointment): AbstractAppointmentStep
    {
        return match ($this) {
            self::Draft => new AppointmentDraftStep($appointment),
            self::Pending => new AppointmentPendingStep($appointment),
            self::Scheduling => new AppointmentSchedulingStep($appointment),
            self::Active => new AppointmentActiveStep($appointment),
            self::Completed, self::Cancelled => new AppointmentDoneStep($appointment),
        };
    }
}
