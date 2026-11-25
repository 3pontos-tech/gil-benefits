<?php

declare(strict_types=1);

use App\Models\Users\User;
use TresPontosTech\Billing\Core\Actions\UpsertSubscription;
use TresPontosTech\Billing\Core\DTOs\SubscriptionDTO;
use TresPontosTech\Billing\Core\Models\Subscriptions\Subscription;

it('grava o desconto concedido na assinatura', function (): void {
    $user = User::factory()->create();

    resolve(UpsertSubscription::class)->handle(discountedSubscriptionDto($user, 'pending', 30));

    expect(Subscription::query()->firstOrFail()->discount_percent)->toBe(30);
});

it('mantém o desconto quando o webhook de ativação não o traz', function (): void {
    $user = User::factory()->create();

    resolve(UpsertSubscription::class)->handle(discountedSubscriptionDto($user, 'pending', 30));
    resolve(UpsertSubscription::class)->handle(discountedSubscriptionDto($user, 'active', null));

    $subscription = Subscription::query()->firstOrFail();

    expect($subscription->stripe_status)->toBe('active')
        ->and($subscription->discount_percent)->toBe(30);
});

it('grava nulo quando a assinatura não tem desconto', function (): void {
    $user = User::factory()->create();

    resolve(UpsertSubscription::class)->handle(discountedSubscriptionDto($user, 'pending', null));

    expect(Subscription::query()->firstOrFail()->discount_percent)->toBeNull();
});

function discountedSubscriptionDto(User $user, string $status, ?int $discountPercent): SubscriptionDTO
{
    return new SubscriptionDTO(
        billableType: (new User)->getMorphClass(),
        billableId: $user->getKey(),
        subscriptionExternalId: 'chk_teste',
        status: $status,
        planExternalId: 'pp',
        planSlug: 'gold',
        quantity: 1,
        endsAt: null,
        discountPercent: $discountPercent,
    );
}
