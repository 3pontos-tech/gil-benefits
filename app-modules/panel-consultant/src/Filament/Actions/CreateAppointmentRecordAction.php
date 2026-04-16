<?php

declare(strict_types=1);

namespace TresPontosTech\Consultants\Filament\Actions;

use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Gate;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use TresPontosTech\Appointments\Actions\Records\CreateAppointmentRecordFromUploadAction;
use TresPontosTech\Appointments\Enums\AppointmentStatus;
use TresPontosTech\Appointments\Models\Appointment;
use TresPontosTech\Appointments\Models\AppointmentRecord;

class CreateAppointmentRecordAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'create-appointment-record';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label(__('panel-consultant::resources.appointment_records.create.label'));
        $this->icon(Heroicon::DocumentArrowUp);
        $this->color('primary');
        $this->modalHeading(__('panel-consultant::resources.appointment_records.create.modal_heading'));
        $this->modalDescription(__('panel-consultant::resources.appointment_records.create.modal_description'));
        $this->modalSubmitActionLabel(__('panel-consultant::resources.appointment_records.create.submit'));

        $this->visible(fn (Appointment $record): bool => $record->status === AppointmentStatus::Completed
            && $record->record === null
            && Gate::allows('create', AppointmentRecord::class));

        $this->authorize(fn (Appointment $record): bool => $record->status === AppointmentStatus::Completed
            && $record->record === null
            && Gate::allows('create', AppointmentRecord::class));

        $this->schema([
            FileUpload::make('source')
                ->label(__('panel-consultant::resources.appointment_records.create.form.document'))
                ->helperText(__('panel-consultant::resources.appointment_records.create.form.document_helper'))
                ->acceptedFileTypes([
                    'application/pdf',
                    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                    'application/msword',
                ])
                ->maxSize(10 * 1024)
                ->storeFiles(false)
                ->required(),
        ]);

        $this->action(function (Appointment $record, array $data): void {
            /** @var TemporaryUploadedFile $file */
            $file = $data['source'];

            resolve(CreateAppointmentRecordFromUploadAction::class)
                ->execute($record, $file);

            Notification::make()
                ->info()
                ->title(__('panel-consultant::resources.appointment_records.create.started.title'))
                ->body(__('panel-consultant::resources.appointment_records.create.started.body'))
                ->send();
        });
    }
}
