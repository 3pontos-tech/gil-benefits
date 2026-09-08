<?php

declare(strict_types=1);

namespace TresPontosTech\Credits\Database\Factories;

use App\Models\Users\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use TresPontosTech\Billing\Core\Enums\BillingProviderEnum;
use TresPontosTech\Company\Models\Company;
use TresPontosTech\Credits\Enums\CreditOrderStatusEnum;
use TresPontosTech\Credits\Models\CreditOrder;
use TresPontosTech\Credits\Models\UserCredit;

/** @extends Factory<CreditOrder> */
class CreditOrderFactory extends Factory
{
    protected $model = CreditOrder::class;

    public function definition(): array
    {
        $quantity = $this->faker->numberBetween(1, 10);

        return [
            'provider' => BillingProviderEnum::checkoutCases()[0],
            'checkout_id' => 'checkout_' . $this->faker->unique()->uuid(),
            'billable_type' => (new User)->getMorphClass(),
            'billable_id' => User::factory(),
            'company_id' => Company::factory(),
            'quantity' => $quantity,
            'amount_cents' => fn (array $attributes): int => UserCredit::priceFor($attributes['quantity']),
            'status' => CreditOrderStatusEnum::Pending,
            'paid_at' => null,
        ];
    }

    public function paid(): self
    {
        return $this->state([
            'status' => CreditOrderStatusEnum::Paid,
            'paid_at' => now(),
        ]);
    }

    public function forCompany(Company $company): self
    {
        return $this->state([
            'billable_type' => $company->getMorphClass(),
            'billable_id' => $company->getKey(),
            'company_id' => $company->getKey(),
        ]);
    }
}
