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

        $userId = $data['user_id'] ?? null;
        $user = is_string($userId) ? User::query()->find($userId) : null;

        // Mirror BookAppointmentAction: when the user has no monthly quota left, the
        // appointment consumes a credit. Resolved before creation, since the new
        // appointment itself counts toward the monthly quota.
        $this->consumesCredit = $user !== null && $user->monthly_appointments_left <= 0;

        // Record which company's benefit programme this session belongs to, otherwise it
        // lands in no company's reporting. The admin panel has no tenant to read, so it
        // comes from the employee's own company rather than the shared default one.
        $data['company_id'] = $user?->employerCompanyId();

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
            companyId: $appointment->company_id,
            appointmentId: $appointment->getKey(),
        )));
    }
}
