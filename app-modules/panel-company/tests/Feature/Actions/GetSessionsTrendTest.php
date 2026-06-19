<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Cache;
use TresPontosTech\Appointments\Enums\AppointmentStatus;
use TresPontosTech\Appointments\Models\Appointment;
use TresPontosTech\Company\Models\Company;
use TresPontosTech\PanelCompany\Actions\Metrics\GetSessionsTrend;
use TresPontosTech\PanelCompany\DTOs\MetricsFilters;
use TresPontosTech\PanelCompany\Support\MetricsPeriod;

beforeEach(fn () => Cache::flush());

it('aggregates total and completed sessions over the window', function (): void {
    $company = Company::factory()->create();
    Appointment::factory()->count(3)->create([
        'company_id' => $company->id,
        'status' => AppointmentStatus::Completed,
        'appointment_at' => now(),
    ]);

    $data = resolve(GetSessionsTrend::class)->handle($company, MetricsPeriod::lastMonths(12), MetricsFilters::none());

    expect($data->completedTotal)->toBe(3)
        ->and($data->totalSeries)->toHaveCount(12)
        ->and($data->completedSeries)->toHaveCount(12)
        ->and($data->labels)->toHaveCount(12);
});

it('buckets sessions by appointment date, not creation date', function (): void {
    $company = Company::factory()->create();

    Appointment::factory()->create([
        'company_id' => $company->id,
        'status' => AppointmentStatus::Completed,
        'created_at' => now()->subMonths(6),
        'appointment_at' => now(),
    ]);

    $data = resolve(GetSessionsTrend::class)->handle($company, MetricsPeriod::lastMonths(12), MetricsFilters::none());

    $series = $data->completedSeries;

    // The session lands in the current month (appointment_at = now), the last bucket,
    // not six months ago (created_at).
    expect($series[array_key_last($series)])->toBe(1)
        ->and($series[0])->toBe(0)
        ->and($data->completedTotal)->toBe(1);
});

it('reports the growth factor when completed sessions grew', function (): void {
    $company = Company::factory()->create();
    Appointment::factory()->create([
        'company_id' => $company->id,
        'status' => AppointmentStatus::Completed,
        'appointment_at' => now()->subMonths(6),
    ]);
    Appointment::factory()->count(3)->create([
        'company_id' => $company->id,
        'status' => AppointmentStatus::Completed,
        'appointment_at' => now(),
    ]);

    $data = resolve(GetSessionsTrend::class)->handle($company, MetricsPeriod::lastMonths(12), MetricsFilters::none());

    expect($data->growthFactor)->toBe(3.0);
});

it('omits the growth factor when completed sessions did not grow', function (): void {
    $company = Company::factory()->create();
    Appointment::factory()->count(3)->create([
        'company_id' => $company->id,
        'status' => AppointmentStatus::Completed,
        'appointment_at' => now()->subMonths(6),
    ]);
    Appointment::factory()->create([
        'company_id' => $company->id,
        'status' => AppointmentStatus::Completed,
        'appointment_at' => now(),
    ]);

    $data = resolve(GetSessionsTrend::class)->handle($company, MetricsPeriod::lastMonths(12), MetricsFilters::none());

    expect($data->growthFactor)->toBeNull();
});

it('omits the growth factor when completed sessions stayed flat', function (): void {
    $company = Company::factory()->create();
    Appointment::factory()->count(2)->create([
        'company_id' => $company->id,
        'status' => AppointmentStatus::Completed,
        'appointment_at' => now()->subMonths(6),
    ]);
    Appointment::factory()->count(2)->create([
        'company_id' => $company->id,
        'status' => AppointmentStatus::Completed,
        'appointment_at' => now(),
    ]);

    $data = resolve(GetSessionsTrend::class)->handle($company, MetricsPeriod::lastMonths(12), MetricsFilters::none());

    expect($data->growthFactor)->toBeNull();
});
