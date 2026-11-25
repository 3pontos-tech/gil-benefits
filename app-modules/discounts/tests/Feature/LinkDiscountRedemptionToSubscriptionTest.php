<?php

declare(strict_types=1);

use App\Models\Users\User;
use TresPontosTech\Billing\Core\DTOs\CheckoutDiscount;
use TresPontosTech\Billing\Core\DTOs\SubscriptionDTO;
use TresPontosTech\Billing\Core\Events\Subscription\SubscriptionCreated;
use TresPontosTech\Discounts\Enums\DiscountRedemptionStatusEnum;
use TresPontosTech\Discounts\Models\DiscountRedemption;

beforeEach(function (): void {
    $this->user = User::factory()->create();

    $this->criarEvento = fn (string $planSlug, ?CheckoutDiscount $discount, string $externalId = 'checkout_novo'): SubscriptionCreated => new SubscriptionCreated(new SubscriptionDTO(
        billableType: $this->user->getMorphClass(),
        billableId: $this->user->getKey(),
        subscriptionExternalId: $externalId,
        status: 'pending',
        planExternalId: null,
        planSlug: $planSlug,
        quantity: 1,
        endsAt: null,
        discount: $discount,
    ));
});

it('prende a reserva aberta da pessoa no plano à assinatura pendente', function (): void {
    $redemption = DiscountRedemption::factory()->reserved()->forBillable($this->user)->create([
        'plan_slug' => 'gold',
        'subscription_external_id' => null,
    ]);

    event(($this->criarEvento)('gold', new CheckoutDiscount(percentOff: 30)));

    expect($redemption->refresh()->subscription_external_id)->toBe('checkout_novo');
});

it('ignora a criação de assinatura sem desconto', function (): void {
    $redemption = DiscountRedemption::factory()->reserved()->forBillable($this->user)->create([
        'plan_slug' => 'gold',
        'subscription_external_id' => null,
    ]);

    event(($this->criarEvento)('gold', null));

    expect($redemption->refresh()->subscription_external_id)->toBeNull();
});

it('não prende reserva de outro plano nem reserva vencida', function (): void {
    $outroPlano = DiscountRedemption::factory()->reserved()->forBillable($this->user)->create([
        'plan_slug' => 'silver',
        'subscription_external_id' => null,
    ]);

    $vencida = DiscountRedemption::factory()->reserved()->forBillable($this->user)->create([
        'plan_slug' => 'gold',
        'subscription_external_id' => null,
        'reserved_until' => now()->subMinute(),
    ]);

    event(($this->criarEvento)('gold', new CheckoutDiscount(percentOff: 30)));

    expect($outroPlano->refresh()->subscription_external_id)->toBeNull()
        ->and($vencida->refresh()->subscription_external_id)->toBeNull()
        ->and($vencida->status)->toBe(DiscountRedemptionStatusEnum::Reserved);
});

it('liga a reserva indicada pela referência mesmo quando outra reserva é mais recente', function (): void {
    $indicada = DiscountRedemption::factory()->reserved()->forBillable($this->user)->create([
        'plan_slug' => 'gold',
        'subscription_external_id' => null,
        'created_at' => now()->subMinute(),
    ]);

    $maisRecente = DiscountRedemption::factory()->reserved()->forBillable($this->user)->create([
        'plan_slug' => 'gold',
        'subscription_external_id' => null,
    ]);

    event(($this->criarEvento)('gold', new CheckoutDiscount(percentOff: 30, reference: $indicada->getKey())));

    expect($indicada->refresh()->subscription_external_id)->toBe('checkout_novo')
        ->and($maisRecente->refresh()->subscription_external_id)->toBeNull();
});
