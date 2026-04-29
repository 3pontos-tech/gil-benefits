<?php

declare(strict_types=1);

namespace TresPontosTech\Billing\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Date;
use TresPontosTech\Billing\Core\Models\BillingCustomer;

class BillingCustomerFactory extends Factory
{
    protected $model = BillingCustomer::class;

    public function definition(): array
    {
        return [
            'billable_type' => $this->faker->word(),
            'billable_id' => $this->faker->word(),
            'provider' => $this->faker->word(),
            'provider_customer_id' => $this->faker->word(),
            'created_at' => Date::now(),
            'updated_at' => Date::now(),
        ];
    }
}
