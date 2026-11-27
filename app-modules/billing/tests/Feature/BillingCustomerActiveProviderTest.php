<?php

declare(strict_types=1);

use TresPontosTech\Billing\Core\Enums\BillingProviderEnum;
use TresPontosTech\Billing\Core\Models\BillingCustomer;
use TresPontosTech\Billing\Core\Models\Plan;
use TresPontosTech\Billing\Core\Models\Price;
use TresPontosTech\Company\Models\Company;

function activeProviderCustomer(Company $company, BillingProviderEnum $provider): void
{
    BillingCustomer::factory()->create([
        'billable_type' => $company->getMorphClass(),
        'billable_id' => $company->getKey(),
        'provider' => $provider,
        'provider_customer_id' => 'cus_' . $provider->value,
    ]);
}

function activeProviderSubscription(Company $company, BillingProviderEnum $provider): void
{
    $plan = Plan::factory()->active()->forProvider($provider)->create();
    $price = Price::factory()->for($plan, 'plan')->create();

    $company->subscriptions()->create([
        'type' => $plan->slug,
        'stripe_id' => 'sub_' . $provider->value,
        'stripe_status' => 'active',
        'stripe_price' => $price->provider_price_id,
        'quantity' => 1,
    ]);
}

it('takes the provider of the active subscription', function (): void {
    $company = Company::factory()->create();
    activeProviderSubscription($company, BillingProviderEnum::Stripe);

    expect(BillingCustomer::getActiveProvider($company))->toBe(BillingProviderEnum::Stripe);
});

it('never answers a retired gateway, which has no driver any more', function (): void {
    $company = Company::factory()->create();
    activeProviderSubscription($company, BillingProviderEnum::Barte);
    activeProviderCustomer($company, BillingProviderEnum::Virtu);

    expect(BillingCustomer::getActiveProvider($company))->toBeNull();
});

it('falls back to the latest customer of an active gateway', function (): void {
    $company = Company::factory()->create();
    activeProviderCustomer($company, BillingProviderEnum::Stripe);
    $this->travel(1)->minute();
    activeProviderCustomer($company, BillingProviderEnum::Barte);

    expect(BillingCustomer::getActiveProvider($company))->toBe(BillingProviderEnum::Stripe);
});
