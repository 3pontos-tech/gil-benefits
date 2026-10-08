<?php

declare(strict_types=1);

namespace TresPontosTech\PanelApp\Filament\Actions;

use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\ViewField;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Support\Icons\Heroicon;
use Livewire\Component;
use Throwable;
use TresPontosTech\Appointments\Actions\RescheduleAppointmentForUserAction;
use TresPontosTech\Appointments\Exceptions\AppointmentStateException;
use TresPontosTech\Appointments\Exceptions\SlotUnavailableException;
use TresPontosTech\Appointments\Models\Appointment;
use TresPontosTech\PanelApp\Filament\Resources\Appointments\Schemas\AppointmentWizard;

/**
 * Lets the beneficiary move their own appointment from the "My appointments" table.
 *
 * The new slot is picked from the same availability the booking wizard offers; the rule for
 * keeping or dropping the consultant lives in RescheduleAppointmentForUserAction, shared with
 * the home wizard and the API.
 */
class RescheduleAppointmentAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'reschedule-appointment';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label(__('panel-app::resources.appointments.reschedule.action_label'));
        $this->icon(Heroicon::ArrowPath);
        $this->color('warning');

        $this->visible(fn (Appointment $record): bool => $record->user_id === auth()->id()
            && $record->canBeRescheduled());

        $this->modalHeading(__('panel-app::resources.appointments.reschedule.modal_heading'));
        $this->modalDescription(__('panel-app::resources.appointments.reschedule.modal_description'));
        $this->modalSubmitActionLabel(__('panel-app::resources.appointments.reschedule.modal_submit_label'));

        $this->form([
            DatePicker::make('date')
                ->label(__('appointments::resources.appointments.wizard.labels.date'))
                ->required()
                ->native(false)
                ->displayFormat('d/m/Y')
                ->minDate(now()->addDays(Appointment::BOOKING_LEAD_DAYS)->format('Y-m-d'))
                ->reactive()
                ->afterStateUpdated(fn (callable $set) => $set('appointment_at', null)),

            ViewField::make('appointment_at')
                ->label(__('appointments::resources.appointments.wizard.labels.available_times'))
                ->view('forms.fields.available-times', [
                    'slots' => fn (Get $get): array => AppointmentWizard::availableSlots($get('date')),
                ])
                ->required()
                ->reactive(),
        ]);

        $this->action(function (Appointment $record, array $data): void {
            $slot = $data['appointment_at'] ?? null;

            try {
                $outcome = resolve(RescheduleAppointmentForUserAction::class)
                    ->handle($record, auth()->user(), is_string($slot) ? $slot : null);
            } catch (AppointmentStateException) {
                Notification::make()
                    ->title(__('panel-app::resources.appointments.reschedule.cannot_reschedule'))
                    ->danger()
                    ->send();

                return;
            } catch (SlotUnavailableException) {
                $this->notifySlotUnavailable();

                return;
            } catch (Throwable $throwable) {
                report($throwable);

                Notification::make()
                    ->title(__('panel-app::resources.appointments.reschedule.failed'))
                    ->danger()
                    ->send();

                return;
            }

            $livewire = $this->getLivewire();
            if ($livewire instanceof Component) {
                $livewire->dispatch('appointment-rescheduled');
            }

            Notification::make()
                ->title(__('panel-app::resources.appointments.reschedule.success'))
                ->body(blank($outcome->appointment->consultant_id)
                    ? __('panel-app::resources.appointments.reschedule.success_body_unassigned')
                    : __('panel-app::resources.appointments.reschedule.success_body_kept_consultant'))
                ->success()
                ->send();

            if (! $outcome->calendarSynced) {
                Notification::make()
                    ->title(__('panel-app::resources.appointments.reschedule.calendar_sync_failed'))
                    ->warning()
                    ->send();
            }
        });
    }

    private function notifySlotUnavailable(): void
    {
        Notification::make()
            ->title(__('panel-app::resources.appointments.reschedule.slot_unavailable'))
            ->danger()
            ->send();
    }
}
