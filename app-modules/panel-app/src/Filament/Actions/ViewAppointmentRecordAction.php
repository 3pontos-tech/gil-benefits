<?php

declare(strict_types=1);

namespace TresPontosTech\App\Filament\Actions;

use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use TresPontosTech\Appointments\Models\Appointment;

class ViewAppointmentRecordAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'view-appointment-record';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label(__('panel-app::resources.appointments.records.view.label'));
        $this->icon(Heroicon::DocumentText);
        $this->color('info');
        $this->modalHeading(__('panel-app::resources.appointments.records.view.modal_heading'));
        $this->modalWidth('4xl');
        $this->modalSubmitAction(false);
        $this->modalCancelActionLabel(__('panel-app::resources.appointments.records.view.close'));

        $this->visible(fn (Appointment $record): bool => $record->record?->isPublished() === true
            && Gate::allows('view', $record->record));

        $this->modalContent(fn (Appointment $record): View => view(
            'panel-app::appointments.record-modal',
            ['record' => $record->record],
        ));
    }
}
