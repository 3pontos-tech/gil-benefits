<?php

declare(strict_types=1);

use TresPontosTech\Billing\Core\Models\Subscriptions\Subscription;
use TresPontosTech\Company\Models\Company;

function subscriptionWithStatus(Company $company, string $status, array $attributes = []): Subscription
{
    return $company->subscriptions()->create(array_merge([
        'type' => 'platinum-company',
        'stripe_id' => 'checkout_' . fake()->unique()->regexify('[a-z0-9]{16}'),
        'stripe_status' => $status,
        'stripe_price' => 'platinum-company-monthly-subsidized',
        'quantity' => 1,
        'ends_at' => null,
    ], $attributes));
}

it('does not grant access to a checkout that was never paid', function (): void {
    $company = Company::factory()->create();

    subscriptionWithStatus($company, 'pending');

    expect($company->subscribed('platinum-company'))->toBeFalse();
});

it('does not grant access to a cancelled or delinquent subscription', function (string $status): void {
    $company = Company::factory()->create();

    subscriptionWithStatus($company, $status);

    expect($company->subscribed('platinum-company'))->toBeFalse();
})->with(['inactive', 'defaulter']);

it('still grants access to an active subscription', function (): void {
    $company = Company::factory()->create();

    subscriptionWithStatus($company, 'active');

    expect($company->subscribed('platinum-company'))->toBeTrue();
});

it('keeps granting access to a stripe trial', function (): void {
    $company = Company::factory()->create();

    subscriptionWithStatus($company, 'trialing', ['trial_ends_at' => now()->addDays(14)]);

    expect($company->subscribed('platinum-company'))->toBeTrue();
});

it('reports a subscription without access as neither active nor valid', function (string $status): void {
    $subscription = subscriptionWithStatus(Company::factory()->create(), $status);

    expect($subscription->active())->toBeFalse()
        ->and($subscription->valid())->toBeFalse();
})->with(['pending', 'inactive', 'defaulter']);

it('excludes subscriptions without access from the grantingAccess scope', function (): void {
    $company = Company::factory()->create();

    $active = subscriptionWithStatus($company, 'active');
    subscriptionWithStatus($company, 'pending');
    subscriptionWithStatus($company, 'inactive');

    $granting = Subscription::query()
        ->where('subscriptionable_id', $company->getKey())
        ->grantingAccess()
        ->pluck('id');

    expect($granting->all())->toBe([$active->getKey()]);
});
