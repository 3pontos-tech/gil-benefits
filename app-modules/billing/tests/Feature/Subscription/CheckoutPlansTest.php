<?php

use TresPontosTech\Billing\Core\Enums\BillableTypeEnum;
use TresPontosTech\Billing\Core\Enums\BillingProviderEnum;
use TresPontosTech\Billing\Core\Models\Plan;
use TresPontosTech\Billing\Core\Models\Price;
use TresPontosTech\Billing\Core\Repositories\EloquentPlanRepository;

// The subscription page shown to new subscribers must only display plans from
// BillingProviderEnum::checkoutCases(). Plans from a gateway that no longer
// sells must NOT appear, preventing users from subscribing via a deprecated one.

it('getCheckoutPlansFor() returns only plans from checkoutCases providers', function (): void {
    // Sem fixar o provider: o teste vale a regra, não o gateway da vez.
    $checkoutPlan = Plan::factory()->active()
        ->forProvider(BillingProviderEnum::checkoutCases()[0])
        ->state(['type' => BillableTypeEnum::User])
        ->create();
    Price::factory()->for($checkoutPlan, 'plan')->create();

    // Plans of a retired gateway and contractual plans must be invisible on the checkout page
    Plan::factory()->active()->barte()->state(['type' => BillableTypeEnum::User])->create();
    Plan::factory()->active()->contractual()->state(['type' => BillableTypeEnum::User])->create();

    $plans = (new EloquentPlanRepository)->getCheckoutPlansFor('user');

    expect($plans)->toHaveCount(1)
        ->and($plans->first()->productId)->toBe($checkoutPlan->provider_product_id);
});

it('getCheckoutPlansFor() does not expose plans of a retired gateway to new subscribers', function (): void {
    Plan::factory()->active()->barte()->state(['type' => BillableTypeEnum::User])->create();

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

it('getPlansFor() returns the plans of every activeCases provider and nothing else', function (): void {
    // getPlansFor() is used by the middleware to CHECK existing subscriptions.
    $activePlanIds = collect(BillingProviderEnum::activeCases())->map(function (BillingProviderEnum $provider): string {
        $plan = Plan::factory()->active()->forProvider($provider)->state(['type' => BillableTypeEnum::User])->create();
        Price::factory()->for($plan, 'plan')->create();

        return $plan->provider_product_id;
    });

    $retiredPlan = Plan::factory()->active()->barte()->state(['type' => BillableTypeEnum::User])->create();
    Price::factory()->for($retiredPlan, 'plan')->create();
    Plan::factory()->active()->contractual()->state(['type' => BillableTypeEnum::User])->create();

    $plans = (new EloquentPlanRepository)->getPlansFor('user');

    expect($plans->map(fn ($plan) => $plan->productId)->sort()->values()->all())
        ->toBe($activePlanIds->sort()->values()->all());
});
