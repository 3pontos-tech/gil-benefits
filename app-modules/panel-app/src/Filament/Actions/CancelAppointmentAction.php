<?php

namespace TresPontosTech\App\Filament\Actions;

use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use TresPontosTech\Appointments\Actions\UserCancelAppointmentAction;
use TresPontosTech\Appointments\Enums\AppointmentStatus;
use TresPontosTech\Appointments\Models\Appointment;

class CancelAppointmentAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'cancel-appointment';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label(__('panel-app::resources.appointments.cancel.action_label'));
        $this->icon(Heroicon::XMark);
        $this->color('danger');

        $this->visible(fn (Appointment $record): bool => in_array($record->status, [
            AppointmentStatus::Pending,
            AppointmentStatus::Active,
        ], strict: true) && $record->appointment_at->isFuture());

        $this->modalHeading(fn (Appointment $record): string => now()->diffInHours($record->appointment_at, absolute: false) >= 24
            ? __('panel-app::resources.appointments.cancel.modal_heading_ontime')
            : __('panel-app::resources.appointments.cancel.modal_heading_late'));

        $this->modalDescription(fn (Appointment $record): string => now()->diffInHours($record->appointment_at, absolute: false) >= 24
            ? __('panel-app::resources.appointments.cancel.modal_description_ontime')
            : __('panel-app::resources.appointments.cancel.modal_description_late'));

        $this->modalSubmitActionLabel(__('panel-app::resources.appointments.cancel.modal_submit_label'));

        $this->requiresConfirmation();

        $this->action(function (Appointment $record): void {
            resolve(UserCancelAppointmentAction::class)->handle($record);

            $this->getLivewire()->dispatch('appointment-cancelled');

            Notification::make()
                ->title(__('panel-app::resources.appointments.cancel.success'))
                ->success()
                ->send();
        });
    }
}
