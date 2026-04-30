<?php

declare(strict_types=1);

use Filament\Actions\Testing\TestAction;
use Illuminate\Support\Facades\Mail;
use TresPontosTech\Appointments\Mail\AppointmentRecordPublishedMail;
use TresPontosTech\Appointments\Models\Appointment;
use TresPontosTech\Appointments\Models\AppointmentRecord;
use TresPontosTech\Consultants\Filament\Actions\ReviewAppointmentRecordAction;
use TresPontosTech\Consultants\Filament\Resources\Appointments\Pages\ListAppointments;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Livewire\livewire;

beforeEach(function (): void {
    $this->consultant = actingAsConsultant();

    $this->appointment = Appointment::factory()
        ->recycle($this->consultant)
        ->create();
});

it('publishes a draft record, saves content and queues the published mail', function (): void {
    Mail::fake();

    AppointmentRecord::factory()
        ->for($this->appointment)
        ->draft()
        ->create(['content' => 'rascunho existente']);

    livewire(ListAppointments::class)
        ->callAction(
            TestAction::make(ReviewAppointmentRecordAction::getDefaultName())->table($this->appointment->refresh()),
            data: ['content' => 'prontuário publicado'],
        )
        ->assertHasNoActionErrors();

    assertDatabaseHas(AppointmentRecord::class, [
        'appointment_id' => $this->appointment->getKey(),
        'content' => 'prontuário publicado',
    ]);

    expect(AppointmentRecord::query()->where('appointment_id', $this->appointment->getKey())->first()->published_at)
        ->not->toBeNull();

    Mail::assertQueued(AppointmentRecordPublishedMail::class);
});

it('saves as draft without publishing and without sending mail', function (): void {
    Mail::fake();

    $record = AppointmentRecord::factory()
        ->for($this->appointment)
        ->draft()
        ->create(['content' => 'rascunho inicial']);

    livewire(ListAppointments::class)
        ->callAction(
            TestAction::make(ReviewAppointmentRecordAction::getDefaultName())
                ->arguments(['as_draft' => true])
                ->table($this->appointment->refresh()),
            data: ['content' => 'rascunho atualizado'],
        )
        ->assertHasNoActionErrors();

    expect($record->refresh())
        ->content->toBe('rascunho atualizado')
        ->published_at->toBeNull();

    Mail::assertNothingQueued();
});

it('updates a published record without resending mail', function (): void {
    Mail::fake();

    $record = AppointmentRecord::factory()
        ->for($this->appointment)
        ->published()
        ->create(['content' => 'publicado original']);

    $originalPublishedAt = $record->published_at;

    livewire(ListAppointments::class)
        ->callAction(
            TestAction::make(ReviewAppointmentRecordAction::getDefaultName())->table($this->appointment->refresh()),
            data: ['content' => 'publicado revisado'],
        )
        ->assertHasNoActionErrors();

    expect($record->refresh())
        ->content->toBe('publicado revisado')
        ->published_at->toEqual($originalPublishedAt);

    Mail::assertNothingQueued();
});

it('requires content', function (): void {
    AppointmentRecord::factory()
        ->for($this->appointment)
        ->draft()
        ->create(['content' => 'rascunho']);

    livewire(ListAppointments::class)
        ->callAction(
            TestAction::make(ReviewAppointmentRecordAction::getDefaultName())->table($this->appointment->refresh()),
            data: ['content' => ''],
        )
        ->assertHasActionErrors(['content' => 'required']);
});

it('is hidden when no record exists', function (): void {
    livewire(ListAppointments::class)
        ->assertActionHidden(
            TestAction::make(ReviewAppointmentRecordAction::getDefaultName())->table($this->appointment),
        );
});

it('is hidden when record has no content yet', function (): void {
    AppointmentRecord::factory()
        ->for($this->appointment)
        ->draft()
        ->create(['content' => null]);

    livewire(ListAppointments::class)
        ->assertActionHidden(
            TestAction::make(ReviewAppointmentRecordAction::getDefaultName())->table($this->appointment->refresh()),
        );
});
