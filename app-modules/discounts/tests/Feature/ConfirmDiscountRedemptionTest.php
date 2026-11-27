<?php

declare(strict_types=1);

use App\Models\Users\User;
use Illuminate\Support\Facades\Log;
use TresPontosTech\Billing\Core\DTOs\SubscriptionDTO;
use TresPontosTech\Billing\Core\Events\Subscription\SubscriptionActivated;
use TresPontosTech\Billing\Core\Events\Subscription\SubscriptionCreated;
use TresPontosTech\Discounts\Enums\DiscountDurationEnum;
use TresPontosTech\Discounts\Enums\DiscountRedemptionStatusEnum;
use TresPontosTech\Discounts\Enums\DiscountValueTypeEnum;
use TresPontosTech\Discounts\Models\Discount;
use TresPontosTech\Discounts\Models\DiscountGrant;
use TresPontosTech\Discounts\Models\DiscountRedemption;

beforeEach(function (): void {
    $this->user = User::factory()->create();
    $this->discount = Discount::factory()->active()->locksPlan()->create();
    $this->grant = DiscountGrant::factory()
        ->for($this->discount)
        ->claimedBy($this->user)
        ->create();

    $this->reservar = fn (string $externalId, array $attributes = []): DiscountRedemption => DiscountRedemption::factory()
        ->for($this->discount)
        ->forBillable($this->user)
        ->create([
            'discount_grant_id' => $this->grant->getKey(),
            'subscription_external_id' => $externalId,
            ...$attributes,
        ]);
});

function activateSubscription(User $user, string $externalId, string $planSlug = 'gold', ?string $checkoutReference = null): void
{
    event(new SubscriptionActivated(new SubscriptionDTO(
        billableType: $user->getMorphClass(),
        billableId: $user->getKey(),
        subscriptionExternalId: $externalId,
        status: 'active',
        planExternalId: null,
        planSlug: $planSlug,
        quantity: 1,
        endsAt: null,
        checkoutReference: $checkoutReference,
    )));
}

it('confirma a reserva ligada ao checkout quando o gateway ativa com o id definitivo', function (): void {
    $redemption = ($this->reservar)('checkout_ref_1');

    activateSubscription($this->user, 'sub_1', checkoutReference: 'checkout_ref_1');

    $redemption->refresh();
    expect($redemption->status)->toBe(DiscountRedemptionStatusEnum::Active)
        ->and($redemption->subscription_external_id)->toBe('sub_1');
});

it('confirms the reservation when the gateway swapped the checkout id while the payment was pending', function (): void {
    $redemption = ($this->reservar)('checkout_ref_1');

    event(new SubscriptionCreated(new SubscriptionDTO(
        billableType: $this->user->getMorphClass(),
        billableId: $this->user->getKey(),
        subscriptionExternalId: 'sub_1',
        status: 'pending',
        planExternalId: null,
        planSlug: 'gold',
        quantity: 1,
        endsAt: null,
        checkoutReference: 'checkout_ref_1',
    )));
    activateSubscription($this->user, 'sub_1');

    $redemption->refresh();
    expect($redemption->status)->toBe(DiscountRedemptionStatusEnum::Active)
        ->and($redemption->subscription_external_id)->toBe('sub_1');
});

it('confirma a reserva e trava o plano e a data do primeiro resgate no pagamento', function (): void {
    $redemption = ($this->reservar)('chk_1');

    activateSubscription($this->user, 'chk_1');

    $redemption->refresh();
    $this->grant->refresh();

    expect($redemption->status)->toBe(DiscountRedemptionStatusEnum::Active)
        ->and($redemption->confirmed_at)->not->toBeNull()
        ->and($this->grant->plan_slug)->toBe('gold')
        ->and($this->grant->first_redeemed_at)->not->toBeNull();
});

it('confirma a reserva válida ou liberada', function (DiscountRedemptionStatusEnum $status): void {
    $redemption = ($this->reservar)('chk_status', ['status' => $status, 'reserved_until' => null]);

    activateSubscription($this->user, 'chk_status');

    expect($redemption->refresh()->status)->toBe(DiscountRedemptionStatusEnum::Active);
})->with([
    'reservada' => DiscountRedemptionStatusEnum::Reserved,
    'liberada' => DiscountRedemptionStatusEnum::Released,
]);

it('confirma a reserva vencida quando o pagamento chega depois', function (): void {
    $redemption = ($this->reservar)('chk_vencida', ['reserved_until' => now()->subHour()]);

    activateSubscription($this->user, 'chk_vencida');

    expect($redemption->refresh()->status)->toBe(DiscountRedemptionStatusEnum::Active)
        ->and($this->grant->refresh()->plan_slug)->toBe('gold');
});

it('confirma também o cupom em valor fixo', function (): void {
    $redemption = ($this->reservar)('chk_fixo', [
        'value_type' => DiscountValueTypeEnum::FixedAmount,
        'percent_off' => null,
        'amount_off_cents' => 5000,
        'duration' => DiscountDurationEnum::Forever,
    ]);

    activateSubscription($this->user, 'chk_fixo');

    expect($redemption->refresh()->status)->toBe(DiscountRedemptionStatusEnum::Active)
        ->and($this->grant->refresh()->plan_slug)->toBe('gold');
});

it('não faz nada quando nenhuma reserva corresponde à assinatura', function (): void {
    $redemption = ($this->reservar)('chk_outra');

    activateSubscription($this->user, 'chk_sem_reserva');

    expect($redemption->refresh()->status)->toBe(DiscountRedemptionStatusEnum::Reserved)
        ->and($this->grant->refresh()->plan_slug)->toBeNull()
        ->and($this->grant->first_redeemed_at)->toBeNull();
});

it('não trava o plano quando o cupom não trava o plano', function (): void {
    $discount = Discount::factory()->active()->create(['locks_plan' => false]);
    $grant = DiscountGrant::factory()->for($discount)->claimedBy($this->user)->create();
    $redemption = DiscountRedemption::factory()->for($discount)->forBillable($this->user)->create([
        'discount_grant_id' => $grant->getKey(),
        'subscription_external_id' => 'chk_livre',
    ]);

    activateSubscription($this->user, 'chk_livre');

    expect($redemption->refresh()->status)->toBe(DiscountRedemptionStatusEnum::Active)
        ->and($grant->refresh()->plan_slug)->toBeNull()
        ->and($grant->first_redeemed_at)->toBeNull();
});

it('não troca um plano já travado', function (): void {
    $this->grant->update(['plan_slug' => 'silver', 'first_redeemed_at' => now()->subMonth()]);
    ($this->reservar)('chk_3');

    activateSubscription($this->user, 'chk_3');

    expect($this->grant->refresh()->plan_slug)->toBe('silver');
});

it('registra um aviso e não trava outro plano depois de travado', function (): void {
    Log::spy();

    $this->grant->update(['plan_slug' => 'silver', 'first_redeemed_at' => now()->subMonth()]);
    $redemption = ($this->reservar)('chk_4');

    activateSubscription($this->user, 'chk_4');

    expect($this->grant->refresh()->plan_slug)->toBe('silver');

    Log::shouldHaveReceived('warning')
        ->once()
        ->with('Assinatura com desconto em plano diferente do travado', [
            'redemption_id' => $redemption->getKey(),
            'grant_id' => $this->grant->getKey(),
            'locked_plan' => 'silver',
            'paid_plan' => 'gold',
        ]);
});

it('não reprocessa uma reserva já confirmada quando o evento chega de novo', function (): void {
    $this->grant->update(['plan_slug' => 'silver', 'first_redeemed_at' => now()->subMonth()]);
    ($this->reservar)('chk_5', ['status' => DiscountRedemptionStatusEnum::Active, 'reserved_until' => null]);
    Log::spy();

    activateSubscription($this->user, 'chk_5');

    expect($this->grant->refresh()->plan_slug)->toBe('silver');
    Log::shouldNotHaveReceived('warning');
});

it('registra um aviso quando a confirmação estoura o limite total do cupom', function (): void {
    Log::spy();

    $this->discount->update(['max_redemptions' => 1]);
    DiscountRedemption::factory()->for($this->discount)->active()->create();
    ($this->reservar)('chk_limite');

    activateSubscription($this->user, 'chk_limite');

    Log::shouldHaveReceived('warning')
        ->once()
        ->with('Cupom pago acima do limite total', [
            'discount_id' => $this->discount->getKey(),
            'max_redemptions' => 1,
            'confirmed_count' => 2,
        ]);
});
