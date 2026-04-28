<?php

declare(strict_types=1);

use Filament\Actions\Testing\TestAction;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use TresPontosTech\Appointments\Enums\AppointmentStatus;
use TresPontosTech\Appointments\Jobs\GenerateAppointmentRecordJob;
use TresPontosTech\Appointments\Models\Appointment;
use TresPontosTech\Appointments\Models\AppointmentRecord;
use TresPontosTech\Consultants\Filament\Actions\CreateAppointmentRecordAction;
use TresPontosTech\Consultants\Filament\Resources\Appointments\Pages\ListAppointments;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Livewire\livewire;

beforeEach(function (): void {
    $this->consultant = actingAsConsultant();

    $this->appointment = Appointment::factory()
        ->recycle($this->consultant)
        ->withStatus(AppointmentStatus::Completed)
        ->create();
});

it('uploads a pdf, creates the record and dispatches the generation job', function (): void {
    Storage::fake('local');
    Queue::fake();

    $file = UploadedFile::fake()->create('record.pdf', 100, 'application/pdf');

    livewire(ListAppointments::class)
        ->callAction(
            TestAction::make(CreateAppointmentRecordAction::getDefaultName())->table($this->appointment),
            data: ['source' => $file],
        )
        ->assertHasNoActionErrors();

    assertDatabaseHas(AppointmentRecord::class, [
        'appointment_id' => $this->appointment->getKey(),
        'published_at' => null,
    ]);

    Queue::assertPushed(GenerateAppointmentRecordJob::class);
});

it('rejects files larger than 10MB', function (): void {
    $file = UploadedFile::fake()->create('large.pdf', 11 * 1024, 'application/pdf');

    livewire(ListAppointments::class)
        ->callAction(
            TestAction::make(CreateAppointmentRecordAction::getDefaultName())->table($this->appointment),
            data: ['source' => $file],
        )
        ->assertHasActionErrors(['source']);
});

it('rejects files with unsupported mime types', function (): void {
    $file = UploadedFile::fake()->create('image.jpg', 100, 'image/jpeg');

    livewire(ListAppointments::class)
        ->callAction(
            TestAction::make(CreateAppointmentRecordAction::getDefaultName())->table($this->appointment),
            data: ['source' => $file],
        )
        ->assertHasActionErrors(['source']);
});

it('requires the source file', function (): void {
    livewire(ListAppointments::class)
        ->callAction(
            TestAction::make(CreateAppointmentRecordAction::getDefaultName())->table($this->appointment),
            data: [],
        )
        ->assertHasActionErrors(['source' => 'required']);
});

it('is hidden when appointment is not completed', function (): void {
    $scheduling = Appointment::factory()
        ->recycle($this->consultant)
        ->withStatus(AppointmentStatus::Scheduling)
        ->create();

    livewire(ListAppointments::class)
        ->assertActionHidden(
            TestAction::make(CreateAppointmentRecordAction::getDefaultName())->table($scheduling),
        );
});

it('is hidden when the appointment already has a record', function (): void {
    AppointmentRecord::factory()->for($this->appointment)->create();

    livewire(ListAppointments::class)
        ->assertActionHidden(
            TestAction::make(CreateAppointmentRecordAction::getDefaultName())->table($this->appointment->refresh()),
        );
});
