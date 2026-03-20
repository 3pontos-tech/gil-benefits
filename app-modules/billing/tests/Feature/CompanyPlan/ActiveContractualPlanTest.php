<?php

use TresPontosTech\Billing\Core\Models\CompanyPlan;
use TresPontosTech\Company\Models\Company;

it('returns the active contractual plan', function (): void {
    $company = Company::factory()->create();
    CompanyPlan::factory()->active()->for($company)->create();

    expect($company->activeContractualPlan())->toBeInstanceOf(CompanyPlan::class);
});

it('returns null when status is not active', function (): void {
    $company = Company::factory()->create();
    CompanyPlan::factory()->inactive()->for($company)->create();

    expect($company->activeContractualPlan())->toBeNull();
});

it('returns null when plan has expired', function (): void {
    $company = Company::factory()->create();
    CompanyPlan::factory()->expired()->for($company)->create();

    expect($company->activeContractualPlan())->toBeNull();
});

it('returns null when plan has not started yet', function (): void {
    $company = Company::factory()->create();
    CompanyPlan::factory()->notStartedYet()->for($company)->create();

    expect($company->activeContractualPlan())->toBeNull();
});

it('returns null when plan is soft deleted', function (): void {
    $company = Company::factory()->create();
    $plan = CompanyPlan::factory()->active()->for($company)->create();
    $plan->delete();

    expect($company->activeContractualPlan())->toBeNull();
});

it('returns null when company has no contractual plan', function (): void {
    $company = Company::factory()->create();

    expect($company->activeContractualPlan())->toBeNull();
});
