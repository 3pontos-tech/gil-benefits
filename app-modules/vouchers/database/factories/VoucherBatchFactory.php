<?php

declare(strict_types=1);

namespace TresPontosTech\Vouchers\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use TresPontosTech\Billing\Core\Models\CompanyPlan;
use TresPontosTech\Vouchers\Models\VoucherBatch;

/** @extends Factory<VoucherBatch> */
class VoucherBatchFactory extends Factory
{
    protected $model = VoucherBatch::class;

    public function definition(): array
    {
        return [
            'name' => 'Campanha ' . $this->faker->word(),
            'quantity' => 10,
            'expires_at' => null,
            'notes' => null,
            'created_by' => null,
        ];
    }

    public function configure(): static
    {
        return $this->afterMaking(function (VoucherBatch $batch): void {
            if ($batch->company_plan_id === null) {
                $plan = CompanyPlan::factory()->active()->creditsOnly()->create();
                $batch->company_plan_id = $plan->getKey();
                $batch->company_id = $plan->company_id;

                return;
            }

            if ($batch->company_id === null) {
                $batch->company_id = CompanyPlan::query()->findOrFail($batch->company_plan_id)->company_id;
            }
        });
    }

    public function forPlan(CompanyPlan $plan): self
    {
        return $this->state([
            'company_plan_id' => $plan->getKey(),
            'company_id' => $plan->company_id,
        ]);
    }

    public function expired(): self
    {
        return $this->state(['expires_at' => now()->subDay()]);
    }
}
