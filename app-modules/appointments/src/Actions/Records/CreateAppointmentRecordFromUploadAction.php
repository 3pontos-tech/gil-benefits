<?php

declare(strict_types=1);

namespace TresPontosTech\Appointments\Actions\Records;

use Illuminate\Http\UploadedFile;
use TresPontosTech\Appointments\Jobs\GenerateAppointmentRecordJob;
use TresPontosTech\Appointments\Models\Appointment;
use TresPontosTech\Appointments\Models\AppointmentRecord;

final readonly class CreateAppointmentRecordFromUploadAction
{
    private const STORAGE_DISK = 'local';

    private const STORAGE_DIRECTORY = 'appointments/records';

    public function execute(Appointment $appointment, UploadedFile $file): AppointmentRecord
    {
        /** @var AppointmentRecord $record */
        $record = $appointment->record()->firstOrCreate([], [
            'content' => null,
            'internal_summary' => null,
            'published_at' => null,
        ]);

        if (! $record->wasRecentlyCreated) {
            return $record;
        }

        $extension = $file->getClientOriginalExtension();
        $filename = sprintf('%s.%s', $record->getKey(), $extension);

        $path = $file->storeAs(self::STORAGE_DIRECTORY, $filename, self::STORAGE_DISK);

        dispatch(new GenerateAppointmentRecordJob($record->id, self::STORAGE_DISK, $path));

        return $record;
    }
}
