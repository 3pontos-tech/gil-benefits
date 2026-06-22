<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Cache;
use TresPontosTech\Billing\Core\Enums\UserCreditStatusEnum;
use TresPontosTech\Billing\Core\Models\UserCredit;
use TresPontosTech\Company\Models\Company;
use TresPontosTech\PanelCompany\Actions\Metrics\GetCreditTotals;

beforeEach(fn () => Cache::flush());

it('aggregates credit counts by status for the tenant owner', function (): void {
    $company = Company::factory()->create();

    UserCredit::factory()->count(2)->create([
        'company_id' => $company->id,
        'owner_id' => $company->owner->getKey(),
        'status' => UserCreditStatusEnum::Available,
    ]);
    UserCredit::factory()->create([
        'company_id' => $company->id,
        'owner_id' => $company->owner->getKey(),
        'status' => UserCreditStatusEnum::Used,
    ]);

    $totals = resolve(GetCreditTotals::class)->handle($company);

    expect($totals->available)->toBe(2)
        ->and($totals->used)->toBe(1)
        ->and($totals->inUse)->toBe(0)
        ->and($totals->total)->toBe(3);
});
