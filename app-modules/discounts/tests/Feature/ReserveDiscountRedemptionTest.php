<?php

declare(strict_types=1);

use App\Models\Users\User;
use TresPontosTech\Billing\Core\DTOs\CheckoutDiscount;
use TresPontosTech\Discounts\Actions\ReserveDiscountRedemption;
use TresPontosTech\Discounts\DTOs\DiscountOffer;
use TresPontosTech\Discounts\Enums\DiscountRedemptionStatusEnum;
use TresPontosTech\Discounts\Models\Discount;
use TresPontosTech\Discounts\Models\DiscountGrant;
use TresPontosTech\Discounts\Models\DiscountRedemption;

beforeEach(function (): void {
    $this->oferta = fn (Discount $discount, ?DiscountGrant $grant = null): DiscountOffer => new DiscountOffer(
        key: $grant instanceof DiscountGrant ? 'grant:' . $grant->getKey() : 'discount:' . $discount->getKey(),
        label: $discount->name,
        checkoutDiscount: new CheckoutDiscount(percentOff: 30),
        listAmountCents: 10000,
        discountedAmountCents: 7000,
        savingPerCycleCents: 3000,
        score: 36000,
        expiresAt: null,
        discount: $discount,
        grant: $grant,
    );

    $this->reservar = fn (User $user, DiscountOffer $offer): ?DiscountRedemption => resolve(ReserveDiscountRedemption::class)->handle($user, $offer, 'plano-essencial');
});

it('cria a reserva com o snapshot do cupom e validade de duas horas', function (): void {
    $user = User::factory()->create();
    $grant = DiscountGrant::factory()->create(['user_id' => $user->getKey()]);
    $offer = ($this->oferta)($grant->discount, $grant);

    $redemption = ($this->reservar)($user, $offer);

    expect($redemption->status)->toBe(DiscountRedemptionStatusEnum::Reserved)
        ->and($redemption->discount->is($grant->discount))->toBeTrue()
        ->and($redemption->grant->is($grant))->toBeTrue()
        ->and($redemption->billable->is($user))->toBeTrue()
        ->and($redemption->plan_slug)->toBe('plano-essencial')
        ->and($redemption->subscription_external_id)->toBeNull()
        ->and($redemption->percent_off)->toBe(30)
        ->and($redemption->list_amount_cents)->toBe(10000)
        ->and($redemption->discounted_amount_cents)->toBe(7000)
        ->and($redemption->reserved_until->between(now()->addHours(2)->subMinute(), now()->addHours(2)->addMinute()))->toBeTrue();
});

it('não cria reserva quando o limite total já foi atingido', function (DiscountRedemptionStatusEnum $status): void {
    $discount = Discount::factory()->create(['max_redemptions' => 1]);
    DiscountRedemption::factory()->create([
        'discount_id' => $discount->getKey(),
        'status' => $status,
        'reserved_until' => $status === DiscountRedemptionStatusEnum::Reserved ? now()->addHour() : null,
    ]);

    $redemption = ($this->reservar)(User::factory()->create(), ($this->oferta)($discount));

    expect($redemption)->toBeNull()
        ->and(DiscountRedemption::query()->where('discount_id', $discount->getKey())->count())->toBe(1);
})->with([
    'ativo' => DiscountRedemptionStatusEnum::Active,
    'concluído' => DiscountRedemptionStatusEnum::Completed,
    'reservado por outra pessoa e ainda válido' => DiscountRedemptionStatusEnum::Reserved,
]);

it('ignora reservas vencidas ao contar o limite total', function (): void {
    $discount = Discount::factory()->create(['max_redemptions' => 1]);
    DiscountRedemption::factory()->create([
        'discount_id' => $discount->getKey(),
        'status' => DiscountRedemptionStatusEnum::Reserved,
        'reserved_until' => now()->subMinute(),
    ]);

    $redemption = ($this->reservar)(User::factory()->create(), ($this->oferta)($discount));

    expect($redemption)->toBeInstanceOf(DiscountRedemption::class);
});

it('não cria reserva quando a pessoa já tem uso ativo do cupom com limite por pessoa', function (): void {
    $user = User::factory()->create();
    $discount = Discount::factory()->create(['max_redemptions_per_customer' => 1]);
    DiscountRedemption::factory()->active()->forBillable($user)->create(['discount_id' => $discount->getKey()]);

    expect(($this->reservar)($user, ($this->oferta)($discount)))->toBeNull()
        ->and(($this->reservar)(User::factory()->create(), ($this->oferta)($discount)))->toBeInstanceOf(DiscountRedemption::class);
});

it('permite reservar de novo quando o cupom não tem limite por pessoa', function (): void {
    $user = User::factory()->create();
    $discount = Discount::factory()->create(['max_redemptions_per_customer' => null]);
    DiscountRedemption::factory()->completed()->forBillable($user)->create(['discount_id' => $discount->getKey()]);

    expect(($this->reservar)($user, ($this->oferta)($discount)))->toBeInstanceOf(DiscountRedemption::class);
});

it('libera a reserva anterior da mesma pessoa ao gerar uma nova', function (): void {
    $user = User::factory()->create();
    $discount = Discount::factory()->create(['max_redemptions_per_customer' => null]);
    $offer = ($this->oferta)($discount);

    $primeira = ($this->reservar)($user, $offer);
    $segunda = ($this->reservar)($user, $offer);

    expect($primeira->refresh()->status)->toBe(DiscountRedemptionStatusEnum::Released)
        ->and($primeira->reserved_until)->toBeNull()
        ->and($segunda->status)->toBe(DiscountRedemptionStatusEnum::Reserved)
        ->and(DiscountRedemption::query()->where('discount_id', $discount->getKey())->where('status', DiscountRedemptionStatusEnum::Reserved)->count())->toBe(1);
});

it('não libera a reserva da pessoa quando a nova tentativa é recusada pelo limite', function (): void {
    $user = User::factory()->create();
    $discount = Discount::factory()->create(['max_redemptions' => 2]);
    DiscountRedemption::factory()->active()->create(['discount_id' => $discount->getKey()]);
    $offer = ($this->oferta)($discount);

    $reserva = ($this->reservar)($user, $offer);
    $discount->update(['max_redemptions' => 1]);

    expect(($this->reservar)($user, $offer))->toBeNull()
        ->and($reserva->refresh()->status)->toBe(DiscountRedemptionStatusEnum::Reserved);
});

it('não mexe na reserva de outra pessoa ao reservar', function (): void {
    $discount = Discount::factory()->create(['max_redemptions' => null]);
    $outraPessoa = User::factory()->create();
    $reservaDaOutra = ($this->reservar)($outraPessoa, ($this->oferta)($discount));

    ($this->reservar)(User::factory()->create(), ($this->oferta)($discount));

    expect($reservaDaOutra->refresh()->status)->toBe(DiscountRedemptionStatusEnum::Reserved);
});
