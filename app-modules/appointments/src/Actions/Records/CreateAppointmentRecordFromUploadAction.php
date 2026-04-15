<?php

declare(strict_types=1);

namespace TresPontosTech\Appointments\Actions\Records;

use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use TresPontosTech\Appointments\Jobs\GenerateAppointmentRecordJob;
use TresPontosTech\Appointments\Models\Appointment;
use TresPontosTech\Appointments\Models\AppointmentRecord;

final readonly class CreateAppointmentRecordFromUploadAction
{
    public function execute(Appointment $appointment, TemporaryUploadedFile $file): AppointmentRecord
    {
        /** @var AppointmentRecord $record */
        $record = $appointment->record()->create([
            'content' => null,
            'internal_summary' => null,
            'published_at' => null,
        ]);

        dispatch(new GenerateAppointmentRecordJob($record->id, $file->getFilename()));

        return $record;
    }
}
