<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Cache;
use TresPontosTech\Appointments\Enums\AppointmentStatus;
use TresPontosTech\Appointments\Models\Appointment;
use TresPontosTech\Company\Models\Company;
use TresPontosTech\Consultants\Models\Consultant;
use TresPontosTech\PanelCompany\Actions\Metrics\GetTopConsultants;
use TresPontosTech\PanelCompany\DTOs\MetricsFilters;
use TresPontosTech\PanelCompany\Support\MetricsPeriod;

beforeEach(fn () => Cache::flush());

it('ranks consultants by session count within the window', function (): void {
    $company = Company::factory()->create();
    $busy = Consultant::factory()->create();
    $quiet = Consultant::factory()->create();

    Appointment::factory()->count(3)->create([
        'company_id' => $company->id,
        'consultant_id' => $busy->id,
        'status' => AppointmentStatus::Completed,
        'appointment_at' => now(),
    ]);
    Appointment::factory()->create([
        'company_id' => $company->id,
        'consultant_id' => $quiet->id,
        'status' => AppointmentStatus::Completed,
        'appointment_at' => now(),
    ]);

    $rows = resolve(GetTopConsultants::class)->handle($company, MetricsPeriod::lastMonths(12), MetricsFilters::none());

    expect($rows)->toHaveCount(2)
        ->and($rows[0]->sessions)->toBe(3)
        ->and($rows[0]->barWidthPercent)->toBe(100.0)
        ->and($rows[1]->sessions)->toBe(1);
});
