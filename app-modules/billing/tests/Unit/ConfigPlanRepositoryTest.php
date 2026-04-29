<?php

use Illuminate\Contracts\Config\Repository;
use TresPontosTech\Billing\Core\Entities\PlanEntity;
use TresPontosTech\Billing\Core\Repositories\ConfigPlanRepository;

function configWithPlans(array $plans): ConfigPlanRepository
{
    config(['cashier.plans' => $plans]);

    return new ConfigPlanRepository(resolve(Repository::class));
}

function fakePlanConfig(array $overrides = []): array
{
    return array_merge([
        'product_id' => 'prod_test',
        'prices' => [
            ['type' => 'default', 'price_id' => 'price_' . uniqid(), 'metadata' => []],
        ],
        'trial_days' => false,
        'has_generic_trial' => false,
        'allow_promotion_codes' => false,
        'collect_tax_ids' => false,
        'metered_price' => false,
    ], $overrides);
}

it('all() returns all plans from config as PlanEntity instances', function (): void {
    $repo = configWithPlans([
        'user_gold' => fakePlanConfig(['product_id' => 'prod_gold']),
        'user_platinum' => fakePlanConfig(['product_id' => 'prod_platinum']),
    ]);

    $plans = $repo->all();

    expect($plans)->toHaveCount(2)
        ->and($plans['user_gold'])->toBeInstanceOf(PlanEntity::class)
        ->and($plans['user_platinum'])->toBeInstanceOf(PlanEntity::class);
});

it('all() returns empty array when no plans are configured', function (): void {
    $repo = configWithPlans([]);

    expect($repo->all())->toBeEmpty();
});

it('get() returns the correct PlanEntity by key', function (): void {
    $repo = configWithPlans([
        'user_gold' => fakePlanConfig(['product_id' => 'prod_gold']),
    ]);

    $plan = $repo->get('user_gold');

    expect($plan)->toBeInstanceOf(PlanEntity::class)
        ->and($plan->productId)->toBe('prod_gold');
});

it('getPlansFor() returns only plans starting with the given prefix', function (): void {
    $repo = configWithPlans([
        'company' => fakePlanConfig(['product_id' => 'prod_company']),
        'user_gold' => fakePlanConfig(['product_id' => 'prod_gold']),
        'user_platinum' => fakePlanConfig(['product_id' => 'prod_platinum']),
    ]);

    $userPlans = $repo->getPlansFor('user');

    expect($userPlans)->toHaveCount(2)
        ->and($userPlans->pluck('productId')->toArray())->toContain('prod_gold', 'prod_platinum');
});

it('getActiveTenantPlan() returns the first plan starting with "company"', function (): void {
    $repo = configWithPlans([
        'user_gold' => fakePlanConfig(['product_id' => 'prod_gold']),
        'company' => fakePlanConfig(['product_id' => 'prod_company']),
    ]);

    $plan = $repo->getActiveTenantPlan();

    expect($plan)->toBeInstanceOf(PlanEntity::class)
        ->and($plan->productId)->toBe('prod_company');
});
