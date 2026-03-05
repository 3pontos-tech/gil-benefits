<?php

use TresPontosTech\Billing\Core\Entities\PlanEntity;
use TresPontosTech\Billing\Core\Models\Plan;
use TresPontosTech\Billing\Core\Models\Price;
use TresPontosTech\Billing\Core\Repositories\EloquentPlanRepository;
use TresPontosTech\Billing\Core\Repositories\PlanRepository;

it('should return an array with plan entities', function (): void {
    $plans = Plan::factory()->active()->count(5)->create();
    Price::factory()->for($plans[0], 'plan')->create();

    $repository = new EloquentPlanRepository;

    $plans = $repository->all();
    expect($plans)->toBeArray();
    expect($plans)->toHaveCount(5);
    expect($plans[0])->toBeInstanceOf(PlanEntity::class);
})->todo();

it('should be an instance of PlanRepository', function (): void {
    $repository = new EloquentPlanRepository;
    expect($repository)->toBeInstanceOf(PlanRepository::class);
});
