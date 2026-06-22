<?php

declare(strict_types=1);

use App\Models\Users\User;
use Illuminate\Support\Facades\Cache;
use TresPontosTech\Appointments\Models\Appointment;
use TresPontosTech\Company\Models\Company;
use TresPontosTech\PanelCompany\Actions\Metrics\GetInsights;
use TresPontosTech\PanelCompany\DTOs\MetricsFilters;
use TresPontosTech\PanelCompany\Support\MetricsPeriod;

beforeEach(fn () => Cache::flush());

it('computes never-used share and a top user', function (): void {
    $company = Company::factory()->create();
    $user = User::factory()->create();
    $idle = User::factory()->create();
    $company->employees()->attach([$user->getKey(), $idle->getKey()]);

    Appointment::factory()->count(2)->create([
        'company_id' => $company->id, 'user_id' => $user->getKey(), 'appointment_at' => now(),
    ]);

    $data = resolve(GetInsights::class)->handle($company, MetricsPeriod::lastMonths(12), MetricsFilters::none());

    expect($data->totalEmployees)->toBe(2)
        ->and($data->neverUsedCount)->toBe(1)
        ->and($data->neverUsedRate)->toBe(50.0)
        ->and($data->topUser)->not->toBeNull()
        ->and($data->topUser->count)->toBe(2)
        ->and($data->volume->current)->toBe(2)
        ->and($data->volume->previous)->toBe(0)
        ->and($data->volume->variation)->toBeNull();
});

it('omits the top user when scoped to a single employee', function (): void {
    $company = Company::factory()->create();
    $user = User::factory()->create();
    $company->employees()->attach($user->getKey());
    Appointment::factory()->create([
        'company_id' => $company->id, 'user_id' => $user->getKey(), 'appointment_at' => now(),
    ]);

    $data = resolve(GetInsights::class)->handle(
        $company,
        MetricsPeriod::lastMonths(12),
        new MetricsFilters(userId: (string) $user->getKey()),
    );

    expect($data->topUser)->toBeNull();
});
