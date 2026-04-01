<?php

use TresPontosTech\Admin\Filament\Widgets\Metrics\KPIsOverview;
use TresPontosTech\Appointments\Enums\AppointmentStatus;
use TresPontosTech\Appointments\Models\Appointment;
use TresPontosTech\Appointments\Models\AppointmentFeedback;
use TresPontosTech\Consultants\Models\Consultant;

use function Pest\Livewire\livewire;

beforeEach(function (): void {
    actingAsAdmin();
});

it('renders successfully', function (): void {
    livewire(KPIsOverview::class)
        ->assertOk();
});

it('shows avg rating based on feedbacks in the period', function (): void {
    $consultant = Consultant::factory()->create();

    $appointments = Appointment::factory()->count(2)
        ->withStatus(AppointmentStatus::Completed)
        ->create(['consultant_id' => $consultant->getKey()]);

    AppointmentFeedback::factory()->recycle($appointments[0])->create(['rating' => 4]);
    AppointmentFeedback::factory()->recycle($appointments[1])->create(['rating' => 2]);

    livewire(KPIsOverview::class)
        ->assertSee('3/5');
});

it('shows zero avg rating when no feedbacks exist', function (): void {
    livewire(KPIsOverview::class)
        ->assertSee('0/5');
});

it('shows avg feedbacks per consultant', function (): void {
    $consultantA = Consultant::factory()->create();
    $consultantB = Consultant::factory()->create();

    $appointmentA1 = Appointment::factory()->withStatus(AppointmentStatus::Completed)->create(['consultant_id' => $consultantA->getKey()]);
    $appointmentA2 = Appointment::factory()->withStatus(AppointmentStatus::Completed)->create(['consultant_id' => $consultantA->getKey()]);
    $appointmentB1 = Appointment::factory()->withStatus(AppointmentStatus::Completed)->create(['consultant_id' => $consultantB->getKey()]);

    AppointmentFeedback::factory()->recycle($appointmentA1)->create();
    AppointmentFeedback::factory()->recycle($appointmentA2)->create();
    AppointmentFeedback::factory()->recycle($appointmentB1)->create();

    livewire(KPIsOverview::class)
        ->assertSee('1.5');
});

it('shows featured consultant with highest weighted score', function (): void {
    $consultantA = Consultant::factory()->create(['name' => 'Ana Costa']);
    $consultantB = Consultant::factory()->create(['name' => 'Bruno Lima']);

    $appointmentA = Appointment::factory()->withStatus(AppointmentStatus::Completed)->create(['consultant_id' => $consultantA->getKey()]);
    $appointmentB = Appointment::factory()->withStatus(AppointmentStatus::Completed)->create(['consultant_id' => $consultantB->getKey()]);

    AppointmentFeedback::factory()->recycle($appointmentA)->create(['rating' => 5]);
    AppointmentFeedback::factory()->recycle($appointmentB)->create(['rating' => 3]);

    livewire(KPIsOverview::class)
        ->assertSee('Ana Costa');
});

it('shows no featured consultant when none have feedbacks', function (): void {
    Appointment::factory()->withStatus(AppointmentStatus::Completed)->create();

    livewire(KPIsOverview::class)
        ->assertSee('—');
});

it('does not feature consultant with higher volume but no rating over one with rating', function (): void {
    $consultantA = Consultant::factory()->create(['name' => 'Consultor Volume']);
    $consultantB = Consultant::factory()->create(['name' => 'Consultor Qualidade']);

    Appointment::factory()->count(5)->withStatus(AppointmentStatus::Completed)->create(['consultant_id' => $consultantA->getKey()]);

    $appointment = Appointment::factory()->withStatus(AppointmentStatus::Completed)->create(['consultant_id' => $consultantB->getKey()]);
    AppointmentFeedback::factory()->recycle($appointment)->create(['rating' => 5]);

    livewire(KPIsOverview::class)
        ->assertSee('Consultor Qualidade')
        ->assertDontSee('Consultor Volume');
});
