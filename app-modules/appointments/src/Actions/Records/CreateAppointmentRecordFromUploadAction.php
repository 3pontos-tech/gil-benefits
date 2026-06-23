<?php

declare(strict_types=1);

namespace TresPontosTech\Appointments\Actions\Records;

use Illuminate\Http\UploadedFile;
use RuntimeException;
use TresPontosTech\Appointments\Jobs\GenerateAppointmentRecordJob;
use TresPontosTech\Appointments\Models\Appointment;
use TresPontosTech\Appointments\Models\AppointmentRecord;

final readonly class CreateAppointmentRecordFromUploadAction
{
    private const string STORAGE_DISK = 'local';

    private const string STORAGE_DIRECTORY = 'appointments/records';

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

        if ($path === false) {
            // Remove o registro recém-criado para não travar novos uploads: na próxima
            // chamada o firstOrCreate o encontraria e o early-return pularia o reprocessamento.
            $record->forceDelete();

            throw new RuntimeException(sprintf('Failed to store appointment record file "%s".', $filename));
        }

        dispatch(new GenerateAppointmentRecordJob($record->id, self::STORAGE_DISK, $path));

        return $record;
    }
}
