<?php

namespace TresPontosTech\Billing\Core\Repositories;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use TresPontosTech\Billing\Core\Entities\PlanEntity;
use TresPontosTech\Billing\Core\Enums\BillableTypeEnum;
use TresPontosTech\Billing\Core\Models\Plan;

class EloquentPlanRepository implements PlanRepository
{
    public function all(): array
    {
        return Plan::query()
            ->where('active', true)
            ->get()
            ->map(fn (Plan $plan) => PlanEntity::fromEloquent($plan))
            ->toArray();
    }

    public function get(string $name): PlanEntity
    {
        return collect($this->all())->firstOrFail(fn (PlanEntity $plan) => $plan->slug === $name);
    }

    public function getPlansFor(string $name): Collection
    {
        return Cache::remember('active_user_plans', 15, fn () => Plan::query()
            ->where('type', BillableTypeEnum::User)
            ->where('active', true)
            ->get()
            ->map(fn (Plan $plan) => PlanEntity::fromEloquent($plan))
        );
    }

    public function getActiveTenantPlan(): PlanEntity
    {
        return Cache::remember('active_tenant_plan', 60, fn () => PlanEntity::fromEloquent(
            Plan::query()
                ->where('type', BillableTypeEnum::Company)
                ->where('active', true)->firstOrFail()
        ));
    }
}
