<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Cache;
use TresPontosTech\Appointments\Enums\AppointmentCategoryEnum;
use TresPontosTech\Appointments\Models\Appointment;
use TresPontosTech\Company\Models\Company;
use TresPontosTech\PanelCompany\Actions\Metrics\GetCategoryMix;
use TresPontosTech\PanelCompany\DTOs\MetricsFilters;
use TresPontosTech\PanelCompany\Support\MetricsPeriod;

beforeEach(fn () => Cache::flush());

it('groups appointments by category within the window', function (): void {
    $company = Company::factory()->create();
    Appointment::factory()->count(2)->create([
        'company_id' => $company->id,
        'category_type' => AppointmentCategoryEnum::PersonalFinance,
        'appointment_at' => now(),
    ]);

    $mix = resolve(GetCategoryMix::class)->handle($company, MetricsPeriod::lastMonths(12), MetricsFilters::none());

    expect($mix->total)->toBe(2)
        ->and($mix->items)->toHaveCount(1)
        ->and($mix->items[0]->value)->toBe(2)
        ->and($mix->items[0]->percent)->toBe(100.0);
});

it('returns an empty mix when there is no activity', function (): void {
    $company = Company::factory()->create();

    $mix = resolve(GetCategoryMix::class)->handle($company, MetricsPeriod::lastMonths(12), MetricsFilters::none());

    expect($mix->total)->toBe(0)->and($mix->items)->toBe([]);
});
