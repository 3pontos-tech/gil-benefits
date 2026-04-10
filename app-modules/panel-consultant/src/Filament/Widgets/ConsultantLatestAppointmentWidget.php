<?php

declare(strict_types=1);

namespace TresPontosTech\Consultants\Filament\Widgets;

use App\Models\Users\User;
use Filament\Widgets\Widget;
use TresPontosTech\Appointments\Enums\AppointmentStatus;
use TresPontosTech\Consultants\Models\Consultant;

class ConsultantLatestAppointmentWidget extends Widget
{
    protected string $view = 'filament.admin.widgets.latest-appointment';

    protected int|string|array $columnSpan = 3;

    protected function getViewData(): array
    {
        /** @var User $user */
        $user = auth()->user();

        /** @var Consultant $consultant */
        $consultant = Consultant::query()->where('consultants.user_id', $user->getKey())->first();

        $appointment = $consultant->appointments()->latest('appointment_at')->first();

        if (! $appointment) {
            return [
                'appointment' => null,
                'status' => null,
                'appointmentAt' => null,
                'meetingUrl' => null,
                'consultantName' => null,
                'hasConfirmedStatus' => false,
            ];
        }

        $hasConfirmedStatus = in_array($appointment->status, [
            AppointmentStatus::Scheduling,
            AppointmentStatus::Active,
            AppointmentStatus::Completed,
        ]);

        return [
            'appointment' => $appointment,
            'status' => $appointment->status,
            'appointmentAt' => $appointment->appointment_at,
            'meetingUrl' => $appointment->meeting_url,
            'consultantName' => $appointment->user,
            'hasConfirmedStatus' => $hasConfirmedStatus,
        ];
    }
}
