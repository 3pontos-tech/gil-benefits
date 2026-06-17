<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Cache;
use TresPontosTech\Billing\Core\Enums\UserCreditStatusEnum;
use TresPontosTech\Billing\Core\Models\UserCredit;
use TresPontosTech\Company\Models\Company;
use TresPontosTech\PanelCompany\Actions\Metrics\GetCreditSeries;
use TresPontosTech\PanelCompany\Support\MetricsPeriod;

beforeEach(fn () => Cache::flush());

it('returns one bucket per month over a 12-month window', function (): void {
    $company = Company::factory()->create();
    UserCredit::factory()->count(3)->create([
        'company_id' => $company->id,
        'owner_id' => $company->owner->getKey(),
        'status' => UserCreditStatusEnum::Available,
        'created_at' => now(),
    ]);

    $series = resolve(GetCreditSeries::class)->handle($company, MetricsPeriod::lastMonths(12));

    expect($series)->toHaveCount(12)
        ->and(array_sum($series))->toBeGreaterThanOrEqual(3);
});

it('counts only the requested status when filtered', function (): void {
    $company = Company::factory()->create();
    $owner = $company->owner->getKey();

    UserCredit::factory()->count(2)->create([
        'company_id' => $company->id,
        'owner_id' => $owner,
        'status' => UserCreditStatusEnum::Available,
        'created_at' => now(),
    ]);
    UserCredit::factory()->count(3)->create([
        'company_id' => $company->id,
        'owner_id' => $owner,
        'status' => UserCreditStatusEnum::Used,
        'created_at' => now(),
    ]);

    $available = resolve(GetCreditSeries::class)->handle($company, MetricsPeriod::lastMonths(12), UserCreditStatusEnum::Available->value);
    $used = resolve(GetCreditSeries::class)->handle($company, MetricsPeriod::lastMonths(12), UserCreditStatusEnum::Used->value);

    expect(array_sum($available))->toBe(2)
        ->and(array_sum($used))->toBe(3);
});
