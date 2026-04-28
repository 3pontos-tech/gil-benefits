<?php

declare(strict_types=1);

use App\Models\Users\User;
use Filament\Actions\Testing\TestAction;
use TresPontosTech\Appointments\Models\Appointment;
use TresPontosTech\Appointments\Models\AppointmentRecord;
use TresPontosTech\Consultants\Filament\Actions\ViewPreviousRecordSummaryAction;
use TresPontosTech\Consultants\Filament\Resources\Appointments\Pages\ListAppointments;

use function Pest\Livewire\livewire;

beforeEach(function (): void {
    $this->consultant = actingAsConsultant();

    $this->client = User::factory()->create();

    $this->currentAppointment = Appointment::factory()
        ->for($this->client, 'user')
        ->for($this->consultant, 'consultant')
        ->create(['appointment_at' => now()]);
});

it('is hidden when the client has no previous appointment with a published record', function (): void {
    livewire(ListAppointments::class)
        ->assertActionHidden(
            TestAction::make(ViewPreviousRecordSummaryAction::getDefaultName())
                ->table($this->currentAppointment),
        );
});

it('is hidden when the previous appointment record is still a draft', function (): void {
    $previous = Appointment::factory()
        ->for($this->client, 'user')
        ->for($this->consultant, 'consultant')
        ->create(['appointment_at' => now()->subDays(7)]);

    AppointmentRecord::factory()
        ->for($previous)
        ->draft()
        ->create(['content' => 'rascunho anterior', 'internal_summary' => 'resumo interno']);

    livewire(ListAppointments::class)
        ->assertActionHidden(
            TestAction::make(ViewPreviousRecordSummaryAction::getDefaultName())
                ->table($this->currentAppointment),
        );
});

it('is visible when the client has a previous published record', function (): void {
    $previous = Appointment::factory()
        ->for($this->client, 'user')
        ->for($this->consultant, 'consultant')
        ->create(['appointment_at' => now()->subDays(7)]);

    AppointmentRecord::factory()
        ->for($previous)
        ->published()
        ->create(['internal_summary' => 'resumo da última sessão do cliente']);

    livewire(ListAppointments::class)
        ->assertActionVisible(
            TestAction::make(ViewPreviousRecordSummaryAction::getDefaultName())
                ->table($this->currentAppointment),
        );
});

it('stays visible when the client has multiple previous published records', function (): void {
    $older = Appointment::factory()
        ->for($this->client, 'user')
        ->for($this->consultant, 'consultant')
        ->create(['appointment_at' => now()->subMonths(2)]);

    $recent = Appointment::factory()
        ->for($this->client, 'user')
        ->for($this->consultant, 'consultant')
        ->create(['appointment_at' => now()->subDays(3)]);

    AppointmentRecord::factory()->for($older)->published()->create(['internal_summary' => 'resumo antigo']);
    AppointmentRecord::factory()->for($recent)->published()->create(['internal_summary' => 'resumo recente']);

    livewire(ListAppointments::class)
        ->assertActionVisible(
            TestAction::make(ViewPreviousRecordSummaryAction::getDefaultName())
                ->table($this->currentAppointment),
        );
});

it('ignores appointments from other clients when resolving the previous record', function (): void {
    $otherClient = User::factory()->create();

    $otherClientPrevious = Appointment::factory()
        ->for($otherClient, 'user')
        ->for($this->consultant, 'consultant')
        ->create(['appointment_at' => now()->subDays(7)]);

    AppointmentRecord::factory()
        ->for($otherClientPrevious)
        ->published()
        ->create(['internal_summary' => 'resumo de outro cliente']);

    livewire(ListAppointments::class)
        ->assertActionHidden(
            TestAction::make(ViewPreviousRecordSummaryAction::getDefaultName())
                ->table($this->currentAppointment),
        );
});
