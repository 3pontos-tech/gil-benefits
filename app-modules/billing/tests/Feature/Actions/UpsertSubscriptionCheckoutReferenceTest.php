<?php

declare(strict_types=1);

use App\Models\Users\User;
use TresPontosTech\Billing\Core\Actions\UpsertSubscription;
use TresPontosTech\Billing\Core\DTOs\SubscriptionDTO;
use TresPontosTech\Billing\Core\Models\Subscriptions\Subscription;

function referencedSubscriptionDto(User $user, string $externalId, string $status, ?string $checkoutReference = null): SubscriptionDTO
{
    return new SubscriptionDTO(
        billableType: $user->getMorphClass(),
        billableId: $user->getKey(),
        subscriptionExternalId: $externalId,
        status: $status,
        planExternalId: 'price_gold',
        planSlug: 'gold',
        quantity: 1,
        endsAt: null,
        checkoutReference: $checkoutReference,
    );
}

it('moves the checkout row to the definitive subscription id instead of inserting another', function (): void {
    $user = User::factory()->create();
    resolve(UpsertSubscription::class)->handle(referencedSubscriptionDto($user, 'checkout_ref_1', 'pending'));

    resolve(UpsertSubscription::class)->handle(referencedSubscriptionDto($user, 'sub_1', 'active', checkoutReference: 'checkout_ref_1'));

    $subscription = Subscription::query()->sole();
    expect($subscription->stripe_id)->toBe('sub_1')
        ->and($subscription->stripe_status)->toBe('active');
});

it('keeps updating the definitive row once the checkout row has moved', function (): void {
    $user = User::factory()->create();
    resolve(UpsertSubscription::class)->handle(referencedSubscriptionDto($user, 'checkout_ref_1', 'pending'));
    resolve(UpsertSubscription::class)->handle(referencedSubscriptionDto($user, 'sub_1', 'active', checkoutReference: 'checkout_ref_1'));

    resolve(UpsertSubscription::class)->handle(referencedSubscriptionDto($user, 'sub_1', 'defaulter', checkoutReference: 'checkout_ref_1'));

    $subscription = Subscription::query()->sole();
    expect($subscription->stripe_id)->toBe('sub_1')
        ->and($subscription->stripe_status)->toBe('defaulter');
});

it('inserts a new row when the reference matches nothing', function (): void {
    $user = User::factory()->create();

    resolve(UpsertSubscription::class)->handle(referencedSubscriptionDto($user, 'sub_1', 'active', checkoutReference: 'checkout_unknown'));

    expect(Subscription::query()->sole()->stripe_id)->toBe('sub_1');
});
