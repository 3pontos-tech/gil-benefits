<?php

declare(strict_types=1);

namespace TresPontosTech\PanelAdmin\Filament\Resources\Appointments\Pages;

use App\Models\Users\User;
use Filament\Resources\Pages\CreateRecord;
use TresPontosTech\Appointments\Enums\AppointmentStatus;
use TresPontosTech\Appointments\Models\Appointment;
use TresPontosTech\Billing\Core\DTOs\CreditDTO;
use TresPontosTech\Billing\Core\Events\Credit\CreditConsumed;
use TresPontosTech\PanelAdmin\Filament\Resources\Appointments\AppointmentResource;

class CreateAppointment extends CreateRecord
{
    protected static string $resource = AppointmentResource::class;

    protected bool $consumesCredit = false;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Respect the appointment state machine: admin-created appointments start as
        // Pending. The consultant is assigned later through the confirmation step
        // (ViewAppointment::confirm_appointment), which handles the agenda booking,
        // the transition to Active and the Google Calendar event.
        $data['status'] = AppointmentStatus::Pending;

        // Mirror BookAppointmentAction: when the user has no monthly quota left, the
        // appointment consumes a credit. Resolved before creation, since the new
        // appointment itself counts toward the monthly quota.
        $user = User::query()->find($data['user_id'] ?? null);
        $this->consumesCredit = $user instanceof User && $user->monthly_appointments_left <= 0;

        return $data;
    }

    protected function afterCreate(): void
    {
        if (! $this->consumesCredit) {
            return;
        }

        /** @var Appointment $appointment */
        $appointment = $this->record;

        event(new CreditConsumed(new CreditDTO(
            holderId: $appointment->user_id,
            appointmentId: $appointment->getKey(),
        )));
    }
}
