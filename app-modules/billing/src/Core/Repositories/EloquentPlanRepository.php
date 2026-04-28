<?php

namespace TresPontosTech\Billing\Core\Repositories;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use TresPontosTech\Billing\Core\Entities\PlanEntity;
use TresPontosTech\Billing\Core\Enums\BillableTypeEnum;
use TresPontosTech\Billing\Core\Enums\BillingProviderEnum;
use TresPontosTech\Billing\Core\Models\Plan;

class EloquentPlanRepository implements PlanRepository
{
    public function all(): array
    {
        return Plan::query()
            ->where('active', true)
            ->where('provider', BillingProviderEnum::Barte)
            ->get()
            ->map(fn (Plan $plan): PlanEntity => PlanEntity::fromEloquent($plan))
            ->all();
    }

    public function get(string $name): PlanEntity
    {
        return collect($this->all())->firstOrFail(fn (PlanEntity $plan): bool => $plan->slug === $name);
    }

    public function getPlansFor(string $name): Collection
    {
        return Cache::remember('active_user_plans', 15, fn () => Plan::query()
            ->where('type', BillableTypeEnum::User)
            ->where('active', true)
            ->where('provider', '=', BillingProviderEnum::Barte)
            ->get()
            ->map(fn (Plan $plan): PlanEntity => PlanEntity::fromEloquent($plan))
        );
    }

    public function getActiveTenantPlan(BillingProviderEnum $provider): PlanEntity
    {
        return Cache::remember("active_tenant_plan_{$provider->value}", 60, fn (): PlanEntity => PlanEntity::fromEloquent(
            Plan::query()
                ->where('type', BillableTypeEnum::Company)
                ->where('active', true)
                ->where('provider', $provider)
                ->firstOrFail()
        ));
    }
}
