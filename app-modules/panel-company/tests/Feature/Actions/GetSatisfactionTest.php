<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Cache;
use TresPontosTech\Appointments\Models\Appointment;
use TresPontosTech\Appointments\Models\AppointmentFeedback;
use TresPontosTech\Company\Models\Company;
use TresPontosTech\PanelCompany\Actions\Metrics\GetSatisfaction;
use TresPontosTech\PanelCompany\DTOs\MetricsFilters;
use TresPontosTech\PanelCompany\Support\MetricsPeriod;

beforeEach(fn () => Cache::flush());

it('computes average, NPS and recommendation share from feedback', function (): void {
    $company = Company::factory()->create();

    foreach ([5, 5, 4, 3] as $rating) {
        $appointment = Appointment::factory()->create([
            'company_id' => $company->id,
            'appointment_at' => now(),
        ]);
        AppointmentFeedback::factory()->create([
            'appointment_id' => $appointment->id,
            'rating' => $rating,
        ]);
    }

    $data = resolve(GetSatisfaction::class)->handle($company, MetricsPeriod::lastMonths(12), MetricsFilters::none());

    expect($data->total)->toBe(4)
        ->and($data->nps)->toBe(25)
        ->and($data->recommend)->toBe(75.0)
        ->and($data->avg)->toBeGreaterThan(4.0);
});

it('excludes soft-deleted appointments from satisfaction', function (): void {
    $company = Company::factory()->create();

    $live = Appointment::factory()->create(['company_id' => $company->id, 'appointment_at' => now()]);
    AppointmentFeedback::factory()->create(['appointment_id' => $live->id, 'rating' => 5]);

    $deleted = Appointment::factory()->create(['company_id' => $company->id, 'appointment_at' => now()]);
    AppointmentFeedback::factory()->create(['appointment_id' => $deleted->id, 'rating' => 1]);
    $deleted->delete();

    $data = resolve(GetSatisfaction::class)->handle($company, MetricsPeriod::lastMonths(12), MetricsFilters::none());

    expect($data->total)->toBe(1)
        ->and($data->avg)->toBe(5.0);
});

it('returns zeros when there is no feedback', function (): void {
    $company = Company::factory()->create();

    $data = resolve(GetSatisfaction::class)->handle($company, MetricsPeriod::lastMonths(12), MetricsFilters::none());

    expect($data->total)->toBe(0)
        ->and($data->avg)->toBe(0.0)
        ->and($data->nps)->toBe(0)
        ->and($data->recommend)->toBe(0.0);
});
