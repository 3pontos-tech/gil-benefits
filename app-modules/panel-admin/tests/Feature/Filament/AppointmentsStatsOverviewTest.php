<?php

declare(strict_types=1);

use Filament\Widgets\StatsOverviewWidget\Stat;
use TresPontosTech\Appointments\Enums\AppointmentStatus;
use TresPontosTech\Appointments\Models\Appointment;
use TresPontosTech\PanelAdmin\Filament\Widgets\AppointmentsStatsOverview;

use function Livewire\invade;
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

it('shows the no-show count and keeps the total closing with the sum of the buckets', function (): void {
    Appointment::factory()->withStatus(AppointmentStatus::Pending)->count(2)->create();
    Appointment::factory()->withStatus(AppointmentStatus::Cancelled)->count(1)->create();
    Appointment::factory()->withStatus(AppointmentStatus::Completed)->count(3)->create();
    Appointment::factory()->withStatus(AppointmentStatus::NoShow)->count(4)->create();

    $widget = livewire(AppointmentsStatsOverview::class)->instance();

    $stats = collect(invade($widget)->getStats());
    $statValue = fn (string $key): int => (int) $stats
        ->sole(fn (Stat $stat): bool => $stat->getLabel() === __('panel-admin::widgets.appointments_stats.' . $key))
        ->getValue();

    $completedCreated = 3;

    expect($statValue('no_shows'))->toBe(4)
        ->and($statValue('total_requests'))->toBe(10)
        ->and($statValue('scheduled') + $statValue('pending') + $statValue('cancellations') + $statValue('no_shows') + $completedCreated)
        ->toBe($statValue('total_requests'));
});
