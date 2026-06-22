<?php

declare(strict_types=1);

use App\Models\Users\User;
use Illuminate\Support\Facades\Cache;
use TresPontosTech\Appointments\Models\Appointment;
use TresPontosTech\Company\Models\Company;
use TresPontosTech\PanelCompany\Actions\Metrics\GetEngagement;
use TresPontosTech\PanelCompany\DTOs\MetricsFilters;
use TresPontosTech\PanelCompany\Support\MetricsPeriod;

beforeEach(fn () => Cache::flush());

it('counts active, inactive and utilization', function (): void {
    $company = Company::factory()->create();
    $active = User::factory()->create();
    $idle = User::factory()->create();
    $company->employees()->attach([$active->getKey(), $idle->getKey()]);

    Appointment::factory()->create([
        'company_id' => $company->id, 'user_id' => $active->getKey(), 'appointment_at' => now(),
    ]);

    $data = resolve(GetEngagement::class)->handle($company, MetricsPeriod::lastMonths(12), MetricsFilters::none());

    expect($data->totalEmployees)->toBe(2)
        ->and($data->activeUsers)->toBe(1)
        ->and($data->inactiveUsers)->toBe(1)
        ->and($data->utilizationRate)->toBe(50.0)
        ->and($data->firstTimeUsers)->toBe(1);
});
