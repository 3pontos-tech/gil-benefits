<?php

declare(strict_types=1);

namespace TresPontosTech\Billing\Core\Repositories;

use Illuminate\Support\Collection;
use TresPontosTech\Billing\Core\Entities\PlanEntity;
use TresPontosTech\Billing\Core\Enums\BillingProviderEnum;

interface PlanRepository
{
    /**
     * @return array<int, PlanEntity>
     */
    public function all(): array;

    public function get(string $name): PlanEntity;

    public function getPlansFor(string $name): Collection;

    public function getActiveTenantPlan(BillingProviderEnum $provider): PlanEntity;
}
