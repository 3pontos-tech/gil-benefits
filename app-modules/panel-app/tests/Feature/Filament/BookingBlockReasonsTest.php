<?php

declare(strict_types=1);

use TresPontosTech\Appointments\Enums\AppointmentStatus;
use TresPontosTech\Appointments\Models\Appointment;
use TresPontosTech\PanelApp\Support\BookingBlockReasons;

use function Pest\Laravel\travelTo;

$ongoing = fn (): string => __('panel-app::widgets.plans_overview.ongoing_appointment');
$noQuota = fn (): string => __('panel-app::widgets.plans_overview.no_appointments_available');

beforeEach(function (): void {
    travelTo('2026-09-03 10:00');
    $this->employee = actingAsEmployee(); // plano contratual: 1 por ciclo
});

it('gives no reason at all while the person can book', function (): void {
    expect(BookingBlockReasons::for($this->employee))->toBe([]);
});

it('blames the ongoing consultation, not the quota', function () use ($ongoing, $noQuota): void {
    Appointment::factory()->withStatus(AppointmentStatus::Pending)->create([
        'user_id' => $this->employee->getKey(),
        'appointment_at' => now()->addDays(3),
        // Reserva de um ciclo que já fechou: a cota está cheia, o bloqueio é só a consulta.
        'created_at' => now()->subMonths(2),
    ]);

    $reasons = BookingBlockReasons::for($this->employee->fresh());

    expect($reasons)->toBe([$ongoing()])
        ->and($reasons)->not->toContain($noQuota());
});

it('blames the quota when the cycle is spent and nothing is pending', function () use ($ongoing, $noQuota): void {
    Appointment::factory()->withStatus(AppointmentStatus::Completed)->create([
        'user_id' => $this->employee->getKey(),
        'appointment_at' => now()->subDays(3),
    ]);

    $reasons = BookingBlockReasons::for($this->employee->fresh());

    expect($reasons)->toBe([$noQuota()])
        ->and($reasons)->not->toContain($ongoing());
});

it('states both when both apply', function () use ($ongoing, $noQuota): void {
    Appointment::factory()->withStatus(AppointmentStatus::Pending)->create([
        'user_id' => $this->employee->getKey(),
        'appointment_at' => now()->addDays(3),
    ]);

    expect(BookingBlockReasons::for($this->employee->fresh()))
        ->toBe([$ongoing(), $noQuota()]);
});
