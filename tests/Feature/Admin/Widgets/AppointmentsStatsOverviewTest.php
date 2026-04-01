<?php

use TresPontosTech\Admin\Filament\Widgets\AppointmentsStatsOverview;
use TresPontosTech\Appointments\Enums\AppointmentStatus;
use TresPontosTech\Appointments\Models\Appointment;

use function Pest\Livewire\livewire;

beforeEach(function (): void {
    actingAsAdmin();
});

it('renders successfully', function (): void {
    livewire(AppointmentsStatsOverview::class)
        ->assertOk();
});

it('shows total count of all appointments regardless of status', function (): void {
    Appointment::factory()->count(3)->withStatus(AppointmentStatus::Draft)->create();
    Appointment::factory()->count(2)->withStatus(AppointmentStatus::Completed)->create();
    Appointment::factory()->count(1)->withStatus(AppointmentStatus::Cancelled)->create();

    livewire(AppointmentsStatsOverview::class)
        ->assertSeeInOrder([__('panel-admin::widgets.appointments_stats.total_requests'), '6']);
});

it('counts scheduled as active appointments with a consultant assigned', function (): void {
    Appointment::factory()->count(3)->withStatus(AppointmentStatus::Active)->create();
    Appointment::factory()->count(2)->withStatus(AppointmentStatus::Active)->withoutConsultant()->create();

    livewire(AppointmentsStatsOverview::class)
        ->assertSeeInOrder([__('panel-admin::widgets.appointments_stats.scheduled'), '3']);
});

it('counts pending as appointments in pending scheduling or active status', function (): void {
    Appointment::factory()->withStatus(AppointmentStatus::Pending)->create();
    Appointment::factory()->withStatus(AppointmentStatus::Scheduling)->create();
    Appointment::factory()->withStatus(AppointmentStatus::Active)->create();
    Appointment::factory()->withStatus(AppointmentStatus::Completed)->create();
    Appointment::factory()->withStatus(AppointmentStatus::Cancelled)->create();

    livewire(AppointmentsStatsOverview::class)
        ->assertSeeInOrder([__('panel-admin::widgets.appointments_stats.pending'), '3']);
});

it('counts cancelled appointments correctly', function (): void {
    Appointment::factory()->count(4)->withStatus(AppointmentStatus::Cancelled)->create();
    Appointment::factory()->count(2)->withStatus(AppointmentStatus::Completed)->create();

    livewire(AppointmentsStatsOverview::class)
        ->assertSeeInOrder([__('panel-admin::widgets.appointments_stats.cancellations'), '4']);
});

it('calculates conclusion rate as completed over total', function (): void {
    Appointment::factory()->count(4)->withStatus(AppointmentStatus::Completed)->create();
    Appointment::factory()->count(6)->withStatus(AppointmentStatus::Cancelled)->create();

    livewire(AppointmentsStatsOverview::class)
        ->assertSeeInOrder([__('panel-admin::widgets.appointments_stats.conclusion_rate'), '40%']);
});

it('calculates cancellation rate as cancelled over non draft appointments', function (): void {
    Appointment::factory()->count(2)->withStatus(AppointmentStatus::Cancelled)->create();
    Appointment::factory()->count(2)->withStatus(AppointmentStatus::Completed)->create();
    Appointment::factory()->count(6)->withStatus(AppointmentStatus::Draft)->create();

    livewire(AppointmentsStatsOverview::class)
        ->assertSeeInOrder([__('panel-admin::widgets.appointments_stats.cancellation_rate'), '50%']);
});

it('shows zero rates when there are no appointments', function (): void {
    livewire(AppointmentsStatsOverview::class)
        ->assertSeeInOrder([__('panel-admin::widgets.appointments_stats.conclusion_rate'), '0%'])
        ->assertSeeInOrder([__('panel-admin::widgets.appointments_stats.cancellation_rate'), '0%']);
});
