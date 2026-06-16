<?php

namespace TresPontosTech\Billing\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use TresPontosTech\Billing\Core\Models\Subscriptions\Subscription;
use TresPontosTech\Company\Models\Company;

/** @extends Factory<Subscription> */
class SubscriptionFactory extends Factory
{
    protected $model = Subscription::class;

    public function definition(): array
    {
        return [
            'subscriptionable_type' => 'company',
            'subscriptionable_id' => Company::factory(),
            'type' => 'default',
            'stripe_id' => 'sub_' . $this->faker->unique()->regexify('[A-Za-z0-9]{24}'),
            'stripe_status' => 'active',
            'stripe_price' => null,
            'quantity' => 1,
            'trial_ends_at' => null,
            'ends_at' => null,
        ];
    }

    public function active(): self
    {
        return $this->state(['stripe_status' => 'active']);
    }

    public function trialing(): self
    {
        return $this->state([
            'stripe_status' => 'trialing',
            'trial_ends_at' => now()->addDays(14),
        ]);
    }

    public function pastDue(): self
    {
        return $this->state(['stripe_status' => 'past_due']);
    }

    public function canceled(): self
    {
        return $this->state(['stripe_status' => 'canceled']);
    }

    public function forCompany(Company $company): self
    {
        return $this->state([
            'subscriptionable_type' => 'company',
            'subscriptionable_id' => $company->id,
        ]);
    }
}
