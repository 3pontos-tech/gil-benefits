<?php

namespace TresPontosTech\User\Filament\App\Widgets;

use App\Models\Users\User;
use Filament\Widgets\Widget;
use TresPontosTech\Appointments\Enums\AppointmentStatus;

class LatestAppointmentWidget extends Widget
{
    protected string $view = 'filament.admin.widgets.latest-appointment';

    protected int|string|array $columnSpan = 2;

    protected function getViewData(): array
    {
        /** @var User $user */
        $user = auth()->user();

        $appointment = $user->appointments()->latest()->first();

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
            'consultantName' => $appointment->consultant?->name,
            'hasConfirmedStatus' => $hasConfirmedStatus,
        ];
    }
}
