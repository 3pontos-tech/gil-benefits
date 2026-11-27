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

    expect($company->subscriptions->grantsAccessTo('platinum-company'))->toBeFalse();
});

it('does not grant access to a cancelled subscription', function (string $status): void {
    $company = Company::factory()->create();

    subscriptionWithStatus($company, $status);

    expect($company->subscriptions->grantsAccessTo('platinum-company'))->toBeFalse();
})->with(['inactive']);

it('still grants access to an active subscription', function (): void {
    $company = Company::factory()->create();

    subscriptionWithStatus($company, 'active');

    expect($company->subscriptions->grantsAccessTo('platinum-company'))->toBeTrue();
});

it('keeps granting access to a stripe trial', function (): void {
    $company = Company::factory()->create();

    subscriptionWithStatus($company, 'trialing', ['trial_ends_at' => now()->addDays(14)]);

    expect($company->subscriptions->grantsAccessTo('platinum-company'))->toBeTrue();
});

it('reports a subscription without access as neither active nor valid', function (string $status): void {
    $subscription = subscriptionWithStatus(Company::factory()->create(), $status);

    expect($subscription->active())->toBeFalse()
        ->and($subscription->valid())->toBeFalse();
})->with(['pending', 'inactive']);

it('decides access from the status, the end date and the trial end date', function (string $status, ?string $endsAt, ?string $trialEndsAt, bool $grantsAccess): void {
    $this->freezeTime();
    $company = Company::factory()->create();
    $subscription = subscriptionWithStatus($company, $status, [
        'ends_at' => $endsAt === null ? null : now()->modify($endsAt),
        'trial_ends_at' => $trialEndsAt === null ? null : now()->modify($trialEndsAt),
    ]);

    $isValid = $subscription->valid();

    expect($isValid)->toBe($grantsAccess);
})->with([
    'active, no end date' => ['active', null, null, true],
    'active, cancelled with the paid period still running' => ['active', '+10 days', null, true],
    'active, paid period already over' => ['active', '-1 day', null, false],
    'trialing, trial running' => ['trialing', null, '+10 days', true],
    'trialing, trial already over' => ['trialing', null, '-1 day', false],
    'past_due, gateway still retrying' => ['past_due', null, null, false],
    'past_due, paid period still running' => ['past_due', '+10 days', null, true],
    'unpaid, gateway gave up retrying' => ['unpaid', null, null, false],
    'incomplete, first payment not confirmed' => ['incomplete', null, null, false],
    'incomplete_expired, first payment never confirmed' => ['incomplete_expired', null, null, false],
    'canceled, paid period still running' => ['canceled', '+10 days', null, true],
    'canceled, paid period already over' => ['canceled', '-1 day', null, false],
    'canceled without an end date' => ['canceled', null, null, false],
    'pending checkout' => ['pending', null, null, false],
    'inactive' => ['inactive', null, null, false],
    'defaulter, gateway still retrying' => ['defaulter', null, null, true],
    'defaulter with the paid period still running' => ['defaulter', '+10 days', null, true],
    'defaulter, paid period already over' => ['defaulter', '-1 day', null, false],
]);

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
