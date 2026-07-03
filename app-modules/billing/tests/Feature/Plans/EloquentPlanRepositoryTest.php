<?php

use TresPontosTech\Billing\Core\Entities\PlanEntity;
use TresPontosTech\Billing\Core\Enums\BillableTypeEnum;
use TresPontosTech\Billing\Core\Enums\BillingProviderEnum;
use TresPontosTech\Billing\Core\Models\Plan;
use TresPontosTech\Billing\Core\Models\Price;
use TresPontosTech\Billing\Core\Repositories\EloquentPlanRepository;
use TresPontosTech\Billing\Core\Repositories\PlanRepository;

it('should be an instance of PlanRepository', function (): void {
    $repository = new EloquentPlanRepository;
    expect($repository)->toBeInstanceOf(PlanRepository::class);
});

it('all() returns only barte plans', function (): void {
    $bartePlan = Plan::factory()->active()->barte()->create();
    Price::factory()->for($bartePlan, 'plan')->create();

    Plan::factory()->active()->contractual()->create();

    $repository = new EloquentPlanRepository;
    $plans = $repository->all();

    expect($plans)->toHaveCount(1);
    expect($plans[0])->toBeInstanceOf(PlanEntity::class);
    expect($plans[0]->productId)->toBe($bartePlan->provider_product_id);
})->skip();

it('getPlansFor() returns only barte plans', function (): void {
    $bartePlan = Plan::factory()->active()->barte()->state(['type' => BillableTypeEnum::User])->create();
    Price::factory()->for($bartePlan, 'plan')->create();

    Plan::factory()->active()->contractual()->state(['type' => BillableTypeEnum::User])->create();

    $repository = new EloquentPlanRepository;
    $plans = $repository->getPlansFor('user');

    expect($plans)->toHaveCount(1);
    expect($plans->first()->productId)->toBe($bartePlan->provider_product_id);
})->skip();

it('all() preserves plans with the same slug across active providers', function (): void {
    $stripePlan = Plan::factory()->active()->stripe()->state(['slug' => 'pro'])->create();
    Price::factory()->for($stripePlan, 'plan')->create();

    $bartePlan = Plan::factory()->active()->barte()->state(['slug' => 'pro'])->create();
    Price::factory()->for($bartePlan, 'plan')->create();

    $repository = new EloquentPlanRepository;
    $plans = collect($repository->all());

    expect($plans)->toHaveCount(2);
    expect($plans->pluck('productId'))
        ->toContain($stripePlan->provider_product_id)
        ->toContain($bartePlan->provider_product_id);
});

it('getActiveTenantPlan() returns only stripe plan', function (): void {
    $stripePlan = Plan::factory()->active()->stripe()->state(['type' => BillableTypeEnum::Company])->create();
    Price::factory()->for($stripePlan, 'plan')->create();

    Plan::factory()->active()->contractual()->state(['type' => BillableTypeEnum::Company])->create();

    $repository = new EloquentPlanRepository;
    $plan = $repository->getActiveTenantPlan(BillingProviderEnum::Stripe);

    expect($plan)->toBeInstanceOf(PlanEntity::class);
    expect($plan->productId)->toBe($stripePlan->provider_product_id);
});
