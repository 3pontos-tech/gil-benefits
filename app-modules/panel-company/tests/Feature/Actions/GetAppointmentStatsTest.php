<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Cache;
use TresPontosTech\Appointments\Enums\AppointmentStatus;
use TresPontosTech\Appointments\Models\Appointment;
use TresPontosTech\Company\Models\Company;
use TresPontosTech\PanelCompany\Actions\Metrics\GetAppointmentStats;
use TresPontosTech\PanelCompany\DTOs\MetricsFilters;
use TresPontosTech\PanelCompany\Support\MetricsPeriod;

beforeEach(fn () => Cache::flush());

it('computes totals and attendance rate', function (): void {
    $company = Company::factory()->create();

    Appointment::factory()->count(3)->create([
        'company_id' => $company->id, 'status' => AppointmentStatus::Completed, 'appointment_at' => now(),
    ]);
    Appointment::factory()->create([
        'company_id' => $company->id, 'status' => AppointmentStatus::Cancelled, 'appointment_at' => now(),
    ]);

    $stats = resolve(GetAppointmentStats::class)->handle($company, MetricsPeriod::lastMonths(12), MetricsFilters::none());

    expect($stats->total)->toBe(4)
        ->and($stats->completed)->toBe(3)
        ->and($stats->cancelled)->toBe(1)
        ->and($stats->finalized)->toBe(4)
        ->and($stats->attendanceRate)->toBe(75.0);
});

it('counts no-show appointments in the attendance rate denominator', function (): void {
    $company = Company::factory()->create();

    Appointment::factory()->count(3)->create([
        'company_id' => $company->id, 'status' => AppointmentStatus::Completed, 'appointment_at' => now(),
    ]);
    Appointment::factory()->count(2)->create([
        'company_id' => $company->id, 'status' => AppointmentStatus::NoShow, 'appointment_at' => now(),
    ]);

    $stats = resolve(GetAppointmentStats::class)->handle($company, MetricsPeriod::lastMonths(12), MetricsFilters::none());

    expect($stats->total)->toBe(5)
        ->and($stats->completed)->toBe(3)
        ->and($stats->finalized)->toBe(5)
        ->and($stats->attendanceRate)->toBe(60.0);
});
