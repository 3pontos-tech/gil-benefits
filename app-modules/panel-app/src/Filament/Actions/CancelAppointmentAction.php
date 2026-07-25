<?php

namespace TresPontosTech\PanelApp\Filament\Actions;

use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Livewire\Component;
use TresPontosTech\Appointments\Actions\Transitions\TransitionData;
use TresPontosTech\Appointments\Enums\AppointmentStatus;
use TresPontosTech\Appointments\Enums\CancellationActor;
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

        $this->modalHeading(fn (Appointment $record): string => $record->isLateCancellation()
            ? __('panel-app::resources.appointments.cancel.modal_heading_late')
            : __('panel-app::resources.appointments.cancel.modal_heading_ontime'));

        $this->modalDescription(fn (Appointment $record): string => $record->isLateCancellation()
            ? __('panel-app::resources.appointments.cancel.modal_description_late', ['hours' => Appointment::CANCELLATION_WINDOW_HOURS])
            : __('panel-app::resources.appointments.cancel.modal_description_ontime'));

        $this->modalSubmitActionLabel(__('panel-app::resources.appointments.cancel.modal_submit_label'));

        $this->requiresConfirmation();

        $this->action(function (Appointment $record): void {
            if ($record->user_id !== auth()->id()) {
                return;
            }

            $record->current_transition->handle(new TransitionData(
                cancellationActor: CancellationActor::User,
                cancelledBy: auth()->user(),
            ));

            $livewire = $this->getLivewire();
            if ($livewire instanceof Component) {
                $livewire->dispatch('appointment-cancelled');
            }

            Notification::make()
                ->title(__('panel-app::resources.appointments.cancel.success'))
                ->success()
                ->send();
        });
    }
}
