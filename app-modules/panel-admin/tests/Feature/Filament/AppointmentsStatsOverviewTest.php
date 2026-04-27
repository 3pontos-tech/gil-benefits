<?php

declare(strict_types=1);

use TresPontosTech\Admin\Filament\Widgets\AppointmentsStatsOverview;
use TresPontosTech\Appointments\Enums\AppointmentStatus;
use TresPontosTech\Appointments\Models\Appointment;

use function Pest\Livewire\livewire;

beforeEach(function (): void {
    actingAsAdmin();
});

it('includes Cancelled appointments in the cancellations count', function (): void {
    Appointment::factory()->withStatus(AppointmentStatus::Cancelled)->count(2)->create();

    livewire(AppointmentsStatsOverview::class)
        ->assertSee('2');
});

it('includes CancelledLate appointments in the cancellations count', function (): void {
    Appointment::factory()->withStatus(AppointmentStatus::Cancelled)->count(1)->create();
    Appointment::factory()->withStatus(AppointmentStatus::CancelledLate)->count(2)->create();

    livewire(AppointmentsStatsOverview::class)
        ->assertSee('3');
});

it('does not include Completed appointments in the cancellations count', function (): void {
    Appointment::factory()->withStatus(AppointmentStatus::Completed)->count(5)->create();

    livewire(AppointmentsStatsOverview::class)
        ->assertSee('0');
});
