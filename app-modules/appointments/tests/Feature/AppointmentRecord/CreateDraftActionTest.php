<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Queue;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use TresPontosTech\Appointments\Actions\Records\CreateAppointmentRecordFromUploadAction;
use TresPontosTech\Appointments\Enums\AppointmentStatus;
use TresPontosTech\Appointments\Jobs\GenerateAppointmentRecordJob;
use TresPontosTech\Appointments\Models\Appointment;
use TresPontosTech\Appointments\Models\AppointmentRecord;

it('cria um record vazio e despacha o job de geração', function (): void {
    Queue::fake();

    $appointment = Appointment::factory()->create([
        'status' => AppointmentStatus::Completed,
    ]);

    $file = Mockery::mock(TemporaryUploadedFile::class);
    $file->shouldReceive('getFilename')->andReturn('livewire-tmp-abc.pdf');
    $file->shouldReceive('getMimeType')->andReturn('application/pdf');
    $file->shouldReceive('getClientOriginalName')->andReturn('reuniao.pdf');
    $file->shouldReceive('getSize')->andReturn(12345);

    $record = resolve(CreateAppointmentRecordFromUploadAction::class)
        ->execute($appointment, $file);

    expect($record)->toBeInstanceOf(AppointmentRecord::class)
        ->and($record->content)->toBeNull()
        ->and($record->published_at)->toBeNull()
        ->and($record->appointment_id)->toBe($appointment->getKey());

    Queue::assertPushed(
        GenerateAppointmentRecordJob::class,
        fn (GenerateAppointmentRecordJob $job): bool => $job->recordId === $record->id
            && $job->temporaryFilename === 'livewire-tmp-abc.pdf'
    );
});
