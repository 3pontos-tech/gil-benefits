<?php

namespace TresPontosTech\Billing\Core;

interface PlanRepository
{
    /**
     * @return array<int, Plan>
     */
    public function all(): array;

    public function get(string $name): Plan;
}
