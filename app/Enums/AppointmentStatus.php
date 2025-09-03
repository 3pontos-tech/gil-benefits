<?php

namespace App\Enums;

use App\Action\Appointments\StateMachine\AbstractAppointmentStep;
use App\Action\Appointments\StateMachine\AppointmentActiveStep;
use App\Action\Appointments\StateMachine\AppointmentDoneStep;
use App\Action\Appointments\StateMachine\AppointmentDraftStep;
use App\Action\Appointments\StateMachine\AppointmentPendingStep;
use App\Action\Appointments\StateMachine\AppointmentSchedulingStep;
use App\Models\Appointment;
use Filament\Support\Colors\Color;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;
use Illuminate\Contracts\Support\Htmlable;

enum AppointmentStatus: string implements HasLabel, HasColor
{
    case Draft = 'draft';

    case Pending = 'pending';

    case Scheduling = 'scheduling';
    case Active = 'active';
    case Completed = 'completed';

    case Cancelled = 'cancelled';

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
