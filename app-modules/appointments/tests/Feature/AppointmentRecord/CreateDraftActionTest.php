<?php

declare(strict_types=1);

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use TresPontosTech\Appointments\Actions\Records\CreateAppointmentRecordFromUploadAction;
use TresPontosTech\Appointments\Enums\AppointmentStatus;
use TresPontosTech\Appointments\Jobs\GenerateAppointmentRecordJob;
use TresPontosTech\Appointments\Models\Appointment;
use TresPontosTech\Appointments\Models\AppointmentRecord;

beforeEach(function (): void {
    Queue::fake();
    Storage::fake('local');
});

it('cria um record vazio, persiste o arquivo em disco durável e despacha o job', function (): void {
    $appointment = Appointment::factory()->create([
        'status' => AppointmentStatus::Completed,
    ]);

    $file = UploadedFile::fake()->create('reuniao.pdf', 100, 'application/pdf');

    $record = resolve(CreateAppointmentRecordFromUploadAction::class)
        ->execute($appointment, $file);

    expect($record)->toBeInstanceOf(AppointmentRecord::class)
        ->and($record->content)->toBeNull()
        ->and($record->published_at)->toBeNull()
        ->and($record->appointment_id)->toBe($appointment->getKey());

    $expectedPath = sprintf('appointments/records/%s.pdf', $record->getKey());

    Storage::disk('local')->assertExists($expectedPath);

    Queue::assertPushed(
        GenerateAppointmentRecordJob::class,
        fn (GenerateAppointmentRecordJob $job): bool => $job->recordId === $record->id
            && $job->disk === 'local'
            && $job->path === $expectedPath
    );
});

it('retorna o mesmo record e não despacha novo job em chamada repetida (idempotência)', function (): void {
    $appointment = Appointment::factory()->create([
        'status' => AppointmentStatus::Completed,
    ]);

    $action = resolve(CreateAppointmentRecordFromUploadAction::class);

    $first = $action->execute($appointment, UploadedFile::fake()->create('reuniao.pdf', 100, 'application/pdf'));
    $second = $action->execute($appointment, UploadedFile::fake()->create('reuniao-again.pdf', 100, 'application/pdf'));

    expect($second->getKey())->toBe($first->getKey());

    Queue::assertPushed(GenerateAppointmentRecordJob::class, 1);
});
