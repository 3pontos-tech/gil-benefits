<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Cache;
use TresPontosTech\Appointments\Enums\AppointmentStatus;
use TresPontosTech\Appointments\Models\Appointment;
use TresPontosTech\Company\Models\Company;
use TresPontosTech\PanelCompany\Actions\Metrics\GetStatusBreakdown;
use TresPontosTech\PanelCompany\DTOs\MetricsFilters;
use TresPontosTech\PanelCompany\Support\MetricsPeriod;

beforeEach(fn () => Cache::flush());

it('breaks appointments down by status with the completed share', function (): void {
    $company = Company::factory()->create();
    Appointment::factory()->count(3)->create([
        'company_id' => $company->id,
        'status' => AppointmentStatus::Completed,
        'appointment_at' => now(),
    ]);
    Appointment::factory()->create([
        'company_id' => $company->id,
        'status' => AppointmentStatus::Cancelled,
        'appointment_at' => now(),
    ]);

    $data = resolve(GetStatusBreakdown::class)->handle($company, MetricsPeriod::lastMonths(12), MetricsFilters::none());

    expect($data->total)->toBe(4)
        ->and($data->completedPercent)->toBe(75.0)
        ->and($data->segments)->toHaveCount(2);
});

it('maps a no_show appointment to the purple color', function (): void {
    $company = Company::factory()->create();
    Appointment::factory()->create([
        'company_id' => $company->id,
        'status' => AppointmentStatus::NoShow,
        'appointment_at' => now(),
    ]);

    $data = resolve(GetStatusBreakdown::class)->handle($company, MetricsPeriod::lastMonths(12), MetricsFilters::none());

    expect($data->segments)->toHaveCount(1)
        ->and($data->segments[0]->color)->toBe('purple');
});
