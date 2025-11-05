<?php

namespace TresPontosTech\Billing\Core;

use Illuminate\Support\Collection;

interface PlanRepository
{
    /**
     * @return array<int, Plan>
     */
    public function all(): array;

    public function get(string $name): Plan;

    public function getPlansFor(string $name): Collection;
}
