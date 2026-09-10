<?php

declare(strict_types=1);

use App\Models\Users\User;
use TresPontosTech\Billing\Core\Actions\ResolveQuotaAllowance;
use TresPontosTech\Billing\Core\Enums\CompanyPlanKindEnum;
use TresPontosTech\Billing\Core\Models\CompanyPlan;
use TresPontosTech\Company\Models\Company;
use TresPontosTech\Permissions\Roles;

function employeeOfCompanyWithPlan(CompanyPlan $plan): User
{
    $user = User::factory()->create();
    $user->companies()->attach($plan->company_id, ['role' => Roles::Employee->value, 'active' => true]);

    return $user->fresh();
}

it('grants no quota when the plan is credits only', function (): void {
    $company = Company::factory()->create();
    $plan = CompanyPlan::factory()->active()->creditsOnly()->for($company)->create();
    $user = employeeOfCompanyWithPlan($plan);

    $allowance = resolve(ResolveQuotaAllowance::class)->for($user, $company->getKey());

    expect($allowance->isEmpty())->toBeTrue()
        ->and($allowance->limit)->toBe(0)
        ->and($allowance->anchor)->toBeNull()
        ->and($user->monthly_appointments_left)->toBe(0);
});

it('keeps granting quota when the plan renews monthly', function (): void {
    $company = Company::factory()->create();
    $plan = CompanyPlan::factory()->active()->for($company)->create(['monthly_appointments_per_employee' => 3]);
    $user = employeeOfCompanyWithPlan($plan);

    $allowance = resolve(ResolveQuotaAllowance::class)->for($user, $company->getKey());

    expect($allowance->isEmpty())->toBeFalse()
        ->and($allowance->limit)->toBe(3);
});

it('still reports the plan as active for access gates', function (): void {
    $company = Company::factory()->create();
    CompanyPlan::factory()->active()->creditsOnly()->for($company)->create();

    expect($company->hasActivePlan())->toBeTrue();
});

it('defaults existing plans to the monthly quota kind', function (): void {
    $plan = CompanyPlan::factory()->create();

    expect($plan->kind)->toBe(CompanyPlanKindEnum::MonthlyQuota)
        ->and($plan->grantsMonthlyQuota())->toBeTrue();
});

it('stores a credits only plan without a monthly quota number', function (): void {
    $plan = CompanyPlan::factory()->creditsOnly()->create();

    expect($plan->monthly_appointments_per_employee)->toBeNull()
        ->and($plan->grantsMonthlyQuota())->toBeFalse();
});
