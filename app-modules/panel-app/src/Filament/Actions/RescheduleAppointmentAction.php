<?php

declare(strict_types=1);

namespace TresPontosTech\PanelApp\Filament\Actions;

use Carbon\CarbonInterface;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\ViewField;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Date;
use Livewire\Component;
use TresPontosTech\Appointments\Actions\SyncAppointmentScheduleAction;
use TresPontosTech\Appointments\Exceptions\SlotUnavailableException;
use TresPontosTech\Appointments\Models\Appointment;
use TresPontosTech\Consultants\Models\Consultant;
use TresPontosTech\PanelApp\Filament\Resources\Appointments\Schemas\AppointmentWizard;

/**
 * Lets the beneficiary move their own appointment.
 *
 * The new slot is picked from the same availability the booking wizard offers. The current
 * consultant is kept when they are free at the new time; otherwise the appointment drops
 * back to Pending for an admin to reassign. Either way the agenda blocks, the calendar
 * event and the meeting link are reconciled by SyncAppointmentScheduleAction — the same
 * path the admin edit screen goes through.
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
                ->minDate(now()->addDays(2)->format('Y-m-d'))
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
            if ($record->user_id !== auth()->id() || ! $record->canBeRescheduled()) {
                return;
            }

            $slot = $data['appointment_at'] ?? null;

            // The picker only renders bookable slots, but the state is set client-side —
            // re-check against availability so a stale or forged value cannot land.
            if (! is_string($slot) || ! array_key_exists($slot, AppointmentWizard::availableSlots($data['date'] ?? null))) {
                $this->notifySlotUnavailable();

                return;
            }

            $slotAt = Date::parse($slot);
            $previousConsultantId = $record->consultant_id;
            $previousAppointmentAt = $record->appointment_at;

            $record->update([
                'appointment_at' => $slotAt,
                'consultant_id' => $this->keepsConsultant($record, $slotAt) ? $previousConsultantId : null,
            ]);

            try {
                $calendarSynced = resolve(SyncAppointmentScheduleAction::class)
                    ->handle($record, $previousConsultantId, $previousAppointmentAt);
            } catch (SlotUnavailableException) {
                // The consultant lost the slot between our check and the locking re-check;
                // SyncAppointmentScheduleAction already restored the original time.
                $this->notifySlotUnavailable();

                return;
            }

            $record->refresh();

            $livewire = $this->getLivewire();
            if ($livewire instanceof Component) {
                $livewire->dispatch('appointment-rescheduled');
            }

            Notification::make()
                ->title(__('panel-app::resources.appointments.reschedule.success'))
                ->body(blank($record->consultant_id)
                    ? __('panel-app::resources.appointments.reschedule.success_body_unassigned')
                    : __('panel-app::resources.appointments.reschedule.success_body_kept_consultant'))
                ->success()
                ->send();

            if (! $calendarSynced) {
                Notification::make()
                    ->title(__('panel-app::resources.appointments.reschedule.calendar_sync_failed'))
                    ->warning()
                    ->send();
            }
        });
    }

    /**
     * Keep the consultant only when they are genuinely free at the new time; the appointment
     * is theirs to lose, and an occupied slot must send it back to the assignment queue.
     */
    private function keepsConsultant(Appointment $record, CarbonInterface $slotAt): bool
    {
        $consultant = $record->loadMissing('consultant')->consultant;

        return $consultant instanceof Consultant && $consultant->isBookableAtTime(
            $slotAt->format('Y-m-d'),
            $slotAt->format('H:i'),
            $slotAt->copy()->addHour()->format('H:i'),
        );
    }

    private function notifySlotUnavailable(): void
    {
        Notification::make()
            ->title(__('panel-app::resources.appointments.reschedule.slot_unavailable'))
            ->danger()
            ->send();
    }
}
