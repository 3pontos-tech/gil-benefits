<?php

declare(strict_types=1);

use App\Models\Users\User;
use TresPontosTech\Billing\Core\DTOs\SubscriptionDTO;
use TresPontosTech\Billing\Core\Events\Subscription\SubscriptionActivated;
use TresPontosTech\Discounts\Models\Discount;
use TresPontosTech\Discounts\Models\DiscountGrant;

beforeEach(function (): void {
    $this->user = User::factory()->create();
    $this->grant = DiscountGrant::factory()
        ->for(Discount::factory()->active())
        ->claimedBy($this->user)
        ->create();
});

function activateSubscription(User $user, string $externalId): void
{
    event(new SubscriptionActivated(new SubscriptionDTO(
        billableType: $user->getMorphClass(),
        billableId: $user->getKey(),
        subscriptionExternalId: $externalId,
        status: 'active',
        planExternalId: null,
        planSlug: 'gold',
        quantity: 1,
        endsAt: null,
    )));
}

it('trava o plano e a data do primeiro resgate no pagamento confirmado', function (): void {
    $this->user->subscriptions()->create([
        'stripe_id' => 'chk_1',
        'discount_percent' => 30,
        'stripe_status' => 'pending',
        'type' => 'gold',
        'quantity' => 1,
    ]);

    activateSubscription($this->user, 'chk_1');

    $this->grant->refresh();

    expect($this->grant->plan_slug)->toBe('gold')
        ->and($this->grant->first_redeemed_at)->not->toBeNull();
});

it('não trava o plano quando a assinatura não tem desconto', function (): void {
    $this->user->subscriptions()->create([
        'stripe_id' => 'chk_2',
        'discount_percent' => null,
        'stripe_status' => 'pending',
        'type' => 'gold',
        'quantity' => 1,
    ]);

    activateSubscription($this->user, 'chk_2');

    $this->grant->refresh();

    expect($this->grant->plan_slug)->toBeNull()
        ->and($this->grant->first_redeemed_at)->toBeNull();
});

it('não troca um plano já travado', function (): void {
    $this->grant->update(['plan_slug' => 'silver', 'first_redeemed_at' => now()->subMonth()]);

    $this->user->subscriptions()->create([
        'stripe_id' => 'chk_3',
        'discount_percent' => 30,
        'stripe_status' => 'pending',
        'type' => 'gold',
        'quantity' => 1,
    ]);

    activateSubscription($this->user, 'chk_3');

    expect($this->grant->refresh()->plan_slug)->toBe('silver');
});
