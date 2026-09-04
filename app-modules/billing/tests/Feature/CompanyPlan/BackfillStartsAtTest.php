<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use TresPontosTech\Billing\Core\Models\CompanyPlan;
use TresPontosTech\Company\Models\Company;

use function Pest\Laravel\travelTo;

/**
 * A migration já rodou quando a suíte começa, então o teste a executa de novo sobre
 * linhas legadas fabricadas na mão — é a única forma de exercitar o backfill, já que
 * o banco de teste nasce sem dado antigo.
 */
function runStartsAtBackfill(): void
{
    $migration = require base_path('app-modules/billing/database/migrations/2026_08_16_001230_backfill_starts_at_on_company_plans.php');

    $migration->up();
}

function legacyContractualPlanWithoutStartDate(string $createdAt): string
{
    $company = Company::factory()->create();

    $plan = CompanyPlan::factory()->create(['company_id' => $company->getKey()]);

    DB::table('company_plans')
        ->where('id', $plan->getKey())
        ->update(['starts_at' => null, 'created_at' => $createdAt]);

    return (string) $plan->getKey();
}

it('fills a null start date with the date the plan was created', function (): void {
    $planId = legacyContractualPlanWithoutStartDate('2025-11-07 22:41:00');

    runStartsAtBackfill();

    expect(CompanyPlan::query()->findOrFail($planId)->starts_at->toDateString())
        ->toBe('2025-11-07');
});

it('leaves an existing start date untouched', function (): void {
    travelTo('2026-08-16 10:00');

    $company = Company::factory()->create();
    $plan = CompanyPlan::factory()->create([
        'company_id' => $company->getKey(),
        'starts_at' => '2026-03-10',
    ]);

    runStartsAtBackfill();

    expect($plan->refresh()->starts_at->toDateString())->toBe('2026-03-10');
});

it('fills every legacy row, not just the first chunk boundary', function (): void {
    $ids = collect(range(1, 3))
        ->map(fn (int $offset): string => legacyContractualPlanWithoutStartDate(
            sprintf('2025-0%d-15 08:00:00', $offset)
        ));

    runStartsAtBackfill();

    expect(CompanyPlan::query()->whereIn('id', $ids)->whereNull('starts_at')->count())->toBe(0);
});
