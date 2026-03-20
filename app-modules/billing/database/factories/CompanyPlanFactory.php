<?php

namespace TresPontosTech\Billing\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Date;
use TresPontosTech\Billing\Core\Enums\BillableTypeEnum;
use TresPontosTech\Billing\Core\Enums\BillingProviderEnum;
use TresPontosTech\Billing\Core\Enums\CompanyPlanStatusEnum;
use TresPontosTech\Billing\Core\Models\CompanyPlan;
use TresPontosTech\Billing\Core\Models\Plan;
use TresPontosTech\Company\Models\Company;

/** @extends Factory<CompanyPlan> */
class CompanyPlanFactory extends Factory
{
    protected $model = CompanyPlan::class;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'plan_id' => Plan::factory()->state([
                'provider' => BillingProviderEnum::Contractual,
                'type' => BillableTypeEnum::Company,
                'provider_product_id' => null,
            ]),
            'seats' => $this->faker->numberBetween(1, 100),
            'monthly_appointments_per_employee' => 1,
            'status' => CompanyPlanStatusEnum::Active,
            'starts_at' => null,
            'ends_at' => null,
            'created_at' => Date::now(),
            'updated_at' => Date::now(),
        ];
    }

    public function active(): self
    {
        return $this->state([
            'status' => CompanyPlanStatusEnum::Active,
            'starts_at' => null,
            'ends_at' => null,
        ]);
    }

    public function inactive(): self
    {
        return $this->state([
            'status' => CompanyPlanStatusEnum::Inactive,
        ]);
    }

    public function expired(): self
    {
        return $this->state([
            'status' => CompanyPlanStatusEnum::Active,
            'ends_at' => Date::now()->subDay(),
        ]);
    }

    public function notStartedYet(): self
    {
        return $this->state([
            'status' => CompanyPlanStatusEnum::Active,
            'starts_at' => Date::now()->addDay(),
        ]);
    }
}
