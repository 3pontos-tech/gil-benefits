<?php

declare(strict_types=1);

use App\Models\Users\User;
use Illuminate\Database\QueryException;
use TresPontosTech\Discounts\Enums\DiscountDurationEnum;
use TresPontosTech\Discounts\Enums\DiscountRedemptionStatusEnum;
use TresPontosTech\Discounts\Enums\DiscountValueTypeEnum;
use TresPontosTech\Discounts\Models\Discount;
use TresPontosTech\Discounts\Models\DiscountGrant;
use TresPontosTech\Discounts\Models\DiscountRedemption;

it('cria a reserva por padrão como reservada, com snapshot do desconto', function (): void {
    $redemption = DiscountRedemption::factory()->create();

    expect($redemption->status)->toBe(DiscountRedemptionStatusEnum::Reserved)
        ->and($redemption->value_type)->toBe(DiscountValueTypeEnum::Percent)
        ->and($redemption->percent_off)->toBe(30)
        ->and($redemption->duration)->toBe(DiscountDurationEnum::Forever)
        ->and($redemption->cycles_applied)->toBe(0)
        ->and($redemption->reserved_until)->not->toBeNull();
});

it('converte os campos de snapshot e os enums do cupom', function (): void {
    $redemption = DiscountRedemption::factory()->create([
        'value_type' => DiscountValueTypeEnum::FixedAmount,
        'percent_off' => null,
        'amount_off_cents' => 5000,
        'duration' => DiscountDurationEnum::Repeating,
        'duration_cycles' => 3,
        'list_amount_cents' => 10000,
        'discounted_amount_cents' => 5000,
    ]);

    $redemption->refresh();

    expect($redemption->value_type)->toBe(DiscountValueTypeEnum::FixedAmount)
        ->and($redemption->amount_off_cents)->toBe(5000)
        ->and($redemption->duration)->toBe(DiscountDurationEnum::Repeating)
        ->and($redemption->duration_cycles)->toBe(3)
        ->and($redemption->list_amount_cents)->toBe(10000)
        ->and($redemption->discounted_amount_cents)->toBe(5000);
});

it('muda de estado com os states da factory', function (): void {
    expect(DiscountRedemption::factory()->active()->create()->status)->toBe(DiscountRedemptionStatusEnum::Active)
        ->and(DiscountRedemption::factory()->completed()->create()->status)->toBe(DiscountRedemptionStatusEnum::Completed)
        ->and(DiscountRedemption::factory()->released()->create()->status)->toBe(DiscountRedemptionStatusEnum::Released)
        ->and(DiscountRedemption::factory()->canceled()->create()->status)->toBe(DiscountRedemptionStatusEnum::Canceled);
});

it('limpa a validade da reserva quando a assinatura fica ativa', function (): void {
    $redemption = DiscountRedemption::factory()->active()->create();

    expect($redemption->reserved_until)->toBeNull()
        ->and($redemption->confirmed_at)->not->toBeNull();
});

it('liga a reserva ao cupom e à concessão, com o inverso nas duas pontas', function (): void {
    $grant = DiscountGrant::factory()->create();
    $redemption = DiscountRedemption::factory()->create([
        'discount_id' => $grant->discount_id,
        'discount_grant_id' => $grant->getKey(),
    ]);

    expect($redemption->discount->is($grant->discount))->toBeTrue()
        ->and($redemption->grant->is($grant))->toBeTrue()
        ->and($grant->discount->redemptions->pluck('id')->all())->toBe([$redemption->getKey()])
        ->and($grant->redemptions->pluck('id')->all())->toBe([$redemption->getKey()]);
});

it('aceita reserva de cupom automático sem concessão', function (): void {
    $redemption = DiscountRedemption::factory()->create([
        'discount_id' => Discount::factory()->automatic(),
    ]);

    expect($redemption->grant)->toBeNull()
        ->and($redemption->discount->redemptions)->toHaveCount(1);
});

it('vincula a reserva ao cliente de forma polimórfica', function (): void {
    $user = User::factory()->create();
    $redemption = DiscountRedemption::factory()->forBillable($user)->create();

    expect($redemption->billable->is($user))->toBeTrue()
        ->and($redemption->billable_type)->toBe((new User)->getMorphClass());
});

it('recusa duas reservas para a mesma assinatura externa', function (): void {
    DiscountRedemption::factory()->create(['subscription_external_id' => 'checkout_mesmo']);

    expect(fn () => DiscountRedemption::factory()->create(['subscription_external_id' => 'checkout_mesmo']))
        ->toThrow(QueryException::class);
});

it('impede apagar um cupom que já tem reservas', function (): void {
    $redemption = DiscountRedemption::factory()->create();

    expect(fn () => $redemption->discount->delete())->toThrow(QueryException::class);
});
