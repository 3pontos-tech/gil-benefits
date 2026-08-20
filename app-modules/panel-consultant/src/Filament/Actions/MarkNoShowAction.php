<?php

declare(strict_types=1);

namespace TresPontosTech\PanelConsultant\Filament\Actions;

use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Throwable;
use TresPontosTech\Appointments\Actions\Transitions\TransitionData;
use TresPontosTech\Appointments\Enums\AppointmentStatus;
use TresPontosTech\Appointments\Exceptions\InvalidTransitionException;
use TresPontosTech\Appointments\Models\Appointment;

class MarkNoShowAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'mark-no-show';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label(__('panel-consultant::resources.appointments.mark_no_show.label'));
        $this->icon(Heroicon::UserMinus);
        $this->color('danger');
        $this->requiresConfirmation();
        $this->modalHeading(__('panel-consultant::resources.appointments.mark_no_show.modal_heading'));
        $this->modalDescription(fn (Appointment $record): string => __('panel-consultant::resources.appointments.mark_no_show.modal_description', [
            'name' => $record->user->name,
            'datetime' => $record->appointment_at->isoFormat('L LT'),
        ]));
        $this->modalSubmitActionLabel(__('panel-consultant::resources.appointments.mark_no_show.submit'));

        $this->visible(fn (Appointment $record): bool => $this->canMarkNoShow($record));

        $this->authorize(fn (Appointment $record): bool => $this->canMarkNoShow($record));

        $this->action(function (Appointment $record): void {
            $record->refresh();

            if (! $this->canMarkNoShow($record)) {
                $this->sendMarkNoShowFailureNotification();

                return;
            }

            try {
                $record->current_transition->handle(new TransitionData(
                    noShowMarkedBy: auth()->user(),
                ));
            } catch (InvalidTransitionException) {
                $this->sendMarkNoShowFailureNotification();

                return;
            } catch (Throwable $exception) {
                report($exception);

                $this->sendMarkNoShowFailureNotification();

                return;
            }

            Notification::make()
                ->success()
                ->title(__('panel-consultant::resources.appointments.mark_no_show.success.title'))
                ->body(__('panel-consultant::resources.appointments.mark_no_show.success.body'))
                ->send();
        });
    }

    /**
     * The appointment must belong to the signed-in consultant, still be Active
     * and already be past its scheduled time.
     */
    private function canMarkNoShow(Appointment $record): bool
    {
        $consultantId = auth()->user()?->consultant?->getKey();

        return $record->status === AppointmentStatus::Active
            && $record->appointment_at->isPast()
            && filled($record->consultant_id)
            && filled($consultantId)
            && $record->consultant_id === $consultantId;
    }

    private function sendMarkNoShowFailureNotification(): void
    {
        Notification::make()
            ->danger()
            ->title(__('panel-consultant::resources.appointments.mark_no_show.failure.title'))
            ->body(__('panel-consultant::resources.appointments.mark_no_show.failure.body'))
            ->send();
    }
}
