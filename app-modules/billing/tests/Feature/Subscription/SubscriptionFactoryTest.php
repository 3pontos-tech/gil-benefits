<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use TresPontosTech\Billing\Core\Models\Subscriptions\Subscription;
use TresPontosTech\Company\Models\Company;

it('builds the module subscription, not the cashier one', function (): void {
    $subscription = Subscription::factory()->create();

    expect($subscription)->toBeInstanceOf(Subscription::class)
        ->and($subscription->getTable())->toBe('billing_subscriptions')
        ->and(DB::table('billing_subscriptions')->where('id', $subscription->getKey())->exists())->toBeTrue();
});

it('attaches the subscription to a company through the polymorphic owner', function (): void {
    $company = Company::factory()->create();

    $subscription = Subscription::factory()->forCompany($company)->create();

    expect($subscription->subscriptionable_id)->toBe($company->getKey())
        ->and($subscription->owner)->toBeInstanceOf(Company::class);
});

it('exposes the status states', function (): void {
    expect(Subscription::factory()->active()->create()->stripe_status)->toBe('active')
        ->and(Subscription::factory()->trialing()->create()->stripe_status)->toBe('trialing')
        ->and(Subscription::factory()->pastDue()->create()->stripe_status)->toBe('past_due')
        ->and(Subscription::factory()->canceled()->create()->stripe_status)->toBe('canceled');
});

it('anchors only the state that is active', function (): void {
    expect(Subscription::factory()->active()->create()->quota_anchor_at)->not->toBeNull()
        ->and(Subscription::factory()->trialing()->create()->quota_anchor_at)->toBeNull();
});
