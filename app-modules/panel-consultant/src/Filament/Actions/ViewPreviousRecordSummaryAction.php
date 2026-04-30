<?php

declare(strict_types=1);

namespace TresPontosTech\Consultants\Filament\Actions;

use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use TresPontosTech\Appointments\Models\Appointment;

class ViewPreviousRecordSummaryAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'view-previous-record-summary';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label(__('panel-consultant::resources.appointment_records.previous_summary.label'));
        $this->icon(Heroicon::ClipboardDocumentList);
        $this->color('gray');

        $this->visible(fn (Appointment $record): bool => $this->resolvePreviousAppointment($record) instanceof Appointment);

        $this->modalHeading(__('panel-consultant::resources.appointment_records.previous_summary.modal_heading'));
        $this->modalDescription(__('panel-consultant::resources.appointment_records.previous_summary.modal_description'));
        $this->modalWidth('2xl');
        $this->modalSubmitAction(false);
        $this->modalCancelActionLabel(__('panel-consultant::resources.appointment_records.previous_summary.close'));

        $this->modalContent(function (Appointment $record): View {
            $previous = $this->resolvePreviousAppointment($record);

            return view('panel-consultant::appointments.previous-summary-modal', [
                'summary' => $previous?->record?->internal_summary,
                'lastAppointmentAt' => $previous?->appointment_at,
            ]);
        });
    }

    private function resolvePreviousAppointment(Appointment $record): ?Appointment
    {
        return Appointment::query()
            ->where('user_id', $record->user_id)
            ->where('appointment_at', '<', $record->appointment_at)
            ->whereHas('record', fn (Builder $q) => $q->whereNotNull('published_at'))
            ->with('record')
            ->latest('appointment_at')
            ->first();
    }
}
