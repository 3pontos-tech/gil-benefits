<?php

use App\Enums\PlanTypeEnum;
use App\Models\Plans\Plan;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $plans = [
            [
                'name' => 'Basic',
                'hours_included' => 5,
                'description' => 'Basic plan for small companies',
                'suggested_employees_count' => 15,
                'types' => [
                    [
                        'type' => PlanTypeEnum::Monthly,
                        'price' => 153330,
                    ],
                    [
                        'type' => PlanTypeEnum::SemiAnnual,
                        'price' => 657129,
                    ],
                    [
                        'type' => PlanTypeEnum::Annual,
                        'price' => 1149975,
                    ],
                ],
            ],
            [
                'name' => 'Start',
                'hours_included' => 10,
                'description' => 'Basic plan for small companies',
                'suggested_employees_count' => 30,
                'types' => [
                    [
                        'type' => PlanTypeEnum::Monthly,
                        'price' => 306660,
                    ],
                    [
                        'type' => PlanTypeEnum::SemiAnnual,
                        'price' => 1314257,
                    ],
                    [
                        'type' => PlanTypeEnum::Annual,
                        'price' => 2299950,
                    ],
                ],
            ],
            [
                'name' => 'Pro',
                'hours_included' => 20,
                'description' => 'Basic plan for small companies',
                'suggested_employees_count' => 70,
                'types' => [
                    [
                        'type' => PlanTypeEnum::Monthly,
                        'price' => 613320,
                    ],
                    [
                        'type' => PlanTypeEnum::SemiAnnual,
                        'price' => 2628514,
                    ],
                    [
                        'type' => PlanTypeEnum::Annual,
                        'price' => 4599900,
                    ],
                ],
            ],
            [
                'name' => 'Plus',
                'hours_included' => 40,
                'description' => 'Basic plan for small companies',
                'suggested_employees_count' => 150,
                'types' => [
                    [
                        'type' => PlanTypeEnum::Monthly,
                        'price' => 1226640,
                    ],
                    [
                        'type' => PlanTypeEnum::SemiAnnual,
                        'price' => 5257029,
                    ],
                    [
                        'type' => PlanTypeEnum::Annual,
                        'price' => 9199800,
                    ],
                ],
            ],
        ];

        foreach ($plans as $plan) {
            $types = $plan['types'];
            unset($plan['types']);

            $plan = Plan::query()->create($plan);

            foreach ($types as $type) {
                $plan->items()->create($type);
            }
        }

    }
};
