<?php

use App\Models\Users\User;
use TresPontosTech\Billing\Core\Enums\BillableTypeEnum;
use TresPontosTech\Billing\Core\Enums\BillingProviderEnum;
use TresPontosTech\Billing\Core\Models\Plan;
use TresPontosTech\Billing\Core\Models\Price;
use TresPontosTech\Billing\Core\Repositories\EloquentPlanRepository;
use TresPontosTech\Company\Models\Company;

// The subscription page shown to new subscribers must only display plans
// from BillingProviderEnum::checkoutCases() — currently Barte.
// Legacy Stripe plans must NOT appear, preventing users from subscribing
// via a deprecated gateway.

it('getCheckoutPlansFor() returns only plans from checkoutCases providers', function (): void {
    $bartePlan = Plan::factory()->active()->barte()->state(['type' => BillableTypeEnum::User])->create();
    Price::factory()->for($bartePlan, 'plan')->create();

    // Stripe and Contractual plans must be invisible on the checkout page
    Plan::factory()->active()->stripe()->state(['type' => BillableTypeEnum::User])->create();
    Plan::factory()->active()->contractual()->state(['type' => BillableTypeEnum::User])->create();

    $plans = (new EloquentPlanRepository)->getCheckoutPlansFor('user');

    expect($plans)->toHaveCount(1)
        ->and($plans->first()->productId)->toBe($bartePlan->provider_product_id);
});

it('getCheckoutPlansFor() does not expose legacy stripe plans to new subscribers', function (): void {
    Plan::factory()->active()->stripe()->state(['type' => BillableTypeEnum::User])->create();

    $plans = (new EloquentPlanRepository)->getCheckoutPlansFor('user');

    expect($plans)->toBeEmpty();
});

it('getCheckoutPlansFor() respects checkoutCases() — only providers listed there have plans visible', function (): void {
    // Verify the coupling: checkoutCases() drives what appears on the checkout page.
    // For each provider in checkoutCases(), create an active user plan and assert it's returned.
    $providers = collect(BillingProviderEnum::checkoutCases());

    foreach ($providers as $provider) {
        // Use the provider-specific factory state to avoid trial_days/has_generic_trial conflict
        $plan = Plan::factory()->active()->{strtolower($provider->value)}()
            ->state(['type' => BillableTypeEnum::User])
            ->create();
        Price::factory()->for($plan, 'plan')->create();
    }

    $plans = (new EloquentPlanRepository)->getCheckoutPlansFor('user');

    expect($plans)->toHaveCount($providers->count());
});

it('getCheckoutPlansFor() returns only tenant-specific plans when the tenant has its own plans', function (): void {
    $owner = User::factory()->companyOwner()->create();
    $flamma = Company::factory()->recycle($owner)->create(['slug' => 'flamma-company']);

    $flammaPlan = Plan::factory()->active()->barte()->state(['type' => BillableTypeEnum::User, 'company_id' => $flamma->id])->create();
    Price::factory()->for($flammaPlan, 'plan')->create();

    // Global plan — must NOT be returned when tenant has specific plans
    Plan::factory()->active()->barte()->state(['type' => BillableTypeEnum::User, 'company_id' => null])->create();

    $plans = (new EloquentPlanRepository)->getCheckoutPlansFor('user', $flamma);

    expect($plans)->toHaveCount(1)
        ->and($plans->first()->productId)->toBe($flammaPlan->provider_product_id);
});

it('getCheckoutPlansFor() falls back to global plans when tenant has no specific plans', function (): void {
    $owner = User::factory()->companyOwner()->create();
    $company = Company::factory()->recycle($owner)->create();

    $globalPlan = Plan::factory()->active()->barte()->state(['type' => BillableTypeEnum::User, 'company_id' => null])->create();
    Price::factory()->for($globalPlan, 'plan')->create();

    // Another tenant's plan — must NOT appear as fallback for this company
    $otherOwner = User::factory()->companyOwner()->create();
    $other = Company::factory()->recycle($otherOwner)->create();
    Plan::factory()->active()->barte()->state(['type' => BillableTypeEnum::User, 'company_id' => $other->id])->create();

    $plans = (new EloquentPlanRepository)->getCheckoutPlansFor('user', $company);

    expect($plans)->toHaveCount(1)
        ->and($plans->first()->productId)->toBe($globalPlan->provider_product_id);
});

it('getPlansFor() still returns plans from all active providers for subscription verification', function (): void {
    // getPlansFor() is used by the middleware to CHECK existing subscriptions,
    // so it must include ALL activeCases() providers — including legacy Stripe.
    $stripePlan = Plan::factory()->active()->stripe()->state(['type' => BillableTypeEnum::User])->create();
    Price::factory()->for($stripePlan, 'plan')->create();

    $bartePlan = Plan::factory()->active()->barte()->state(['type' => BillableTypeEnum::User])->create();
    Price::factory()->for($bartePlan, 'plan')->create();

    Plan::factory()->active()->contractual()->state(['type' => BillableTypeEnum::User])->create();

    $plans = (new EloquentPlanRepository)->getPlansFor('user');

    $productIds = $plans->map(fn ($p) => $p->productId);

    expect($plans)->toHaveCount(2)
        ->and($productIds)->toContain($stripePlan->provider_product_id)
        ->and($productIds)->toContain($bartePlan->provider_product_id);
});
