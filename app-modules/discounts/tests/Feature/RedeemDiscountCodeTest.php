<?php

declare(strict_types=1);

use App\Models\Users\User;
use Illuminate\Validation\ValidationException;
use TresPontosTech\Discounts\Actions\RedeemDiscountCode;
use TresPontosTech\Discounts\Enums\DiscountCustomerTypeEnum;
use TresPontosTech\Discounts\Enums\DiscountRedemptionStatusEnum;
use TresPontosTech\Discounts\Models\Discount;
use TresPontosTech\Discounts\Models\DiscountGrant;
use TresPontosTech\Discounts\Models\DiscountRedemption;

beforeEach(function (): void {
    $this->user = User::factory()->create(['email' => 'ana@example.com', 'name' => 'Ana Souza']);

    $this->resgatar = fn (string $code, ?User $user = null): DiscountGrant => resolve(RedeemDiscountCode::class)->handle($user ?? $this->user, $code);
});

it('transforma o código digitado em concessão na carteira da pessoa', function (): void {
    Discount::factory()->code('CLAU10')->create(['claim_window_days' => 30]);

    $grant = ($this->resgatar)('CLAU10');

    expect($grant->user_id)->toBe($this->user->getKey())
        ->and($grant->email)->toBe('ana@example.com')
        ->and($grant->name)->toBe('Ana Souza')
        ->and($grant->source)->toBe('code')
        ->and($grant->claimed_at)->not->toBeNull()
        ->and($grant->expires_at->between(now()->addDays(29), now()->addDays(31)))->toBeTrue()
        ->and(DiscountGrant::query()->where('user_id', $this->user->getKey())->count())->toBe(1);
});

it('normaliza o código digitado antes de procurar o cupom', function (string $digitado): void {
    Discount::factory()->code(' clau 10 ')->create(['claim_window_days' => 30]);

    $grant = ($this->resgatar)($digitado);

    expect($grant->discount->code)->toBe('CLAU10');
})->with(['maiúsculo' => 'CLAU10', 'minúsculo' => 'clau10', 'com espaços' => ' Clau 10 ']);

it('limita a validade da concessão ao fim do cupom quando ele termina antes da janela', function (): void {
    $discount = Discount::factory()->code('CLAU10')->create([
        'claim_window_days' => 180,
        'ends_at' => now()->addDays(5),
    ]);

    $grant = ($this->resgatar)('CLAU10');

    expect($grant->expires_at->timestamp)->toBe($discount->ends_at->timestamp);
});

it('recusa código inválido, inativo, fora da janela ou de empresa sem conceder nada', function (array $atributos): void {
    Discount::factory()->code('CLAU10')->create(['claim_window_days' => 30, ...$atributos]);

    expect(fn () => ($this->resgatar)('CLAU10'))->toThrow(ValidationException::class);
    expect(DiscountGrant::query()->count())->toBe(0);
})->with([
    'inativo' => [['active' => false]],
    'antes da janela' => [['starts_at' => now()->addDay()]],
    'depois da janela' => [['ends_at' => now()->subDay()]],
    'de empresa' => [['customer_type' => DiscountCustomerTypeEnum::Company]],
]);

it('recusa código que não existe', function (): void {
    Discount::factory()->code('CLAU10')->create(['claim_window_days' => 30]);

    expect(fn () => ($this->resgatar)('OUTRO'))->toThrow(ValidationException::class);
    expect(DiscountGrant::query()->count())->toBe(0);
});

it('explica a recusa no campo código', function (): void {
    expect(fn () => ($this->resgatar)('NAOEXISTE'))->toThrow(function (ValidationException $exception): void {
        expect($exception->errors()['code'])->toBe(['Código inválido ou vencido.']);
    });
});

it('não concede o mesmo código duas vezes à mesma pessoa', function (): void {
    Discount::factory()->code('CLAU10')->create(['claim_window_days' => 30]);

    ($this->resgatar)('CLAU10');

    expect(fn () => ($this->resgatar)('CLAU10'))->toThrow(function (ValidationException $exception): void {
        expect($exception->errors()['code'])->toBe(['Você já resgatou este cupom.']);
    });
    expect(DiscountGrant::query()->where('user_id', $this->user->getKey())->count())->toBe(1);
});

it('recusa quando o limite total de usos já foi atingido', function (): void {
    $discount = Discount::factory()->code('CLAU10')->create([
        'claim_window_days' => 30,
        'max_redemptions' => 1,
    ]);
    DiscountRedemption::factory()
        ->for($discount)
        ->forBillable(User::factory()->create())
        ->create(['status' => DiscountRedemptionStatusEnum::Active]);

    expect(fn () => ($this->resgatar)('CLAU10'))->toThrow(function (ValidationException $exception): void {
        expect($exception->errors()['code'])->toBe(['Este cupom já atingiu o limite de usos.']);
    });
    expect(DiscountGrant::query()->count())->toBe(0);
});

it('não conta reserva vencida no limite total de usos', function (): void {
    $discount = Discount::factory()->code('CLAU10')->create([
        'claim_window_days' => 30,
        'max_redemptions' => 1,
    ]);
    DiscountRedemption::factory()
        ->for($discount)
        ->forBillable(User::factory()->create())
        ->create([
            'status' => DiscountRedemptionStatusEnum::Reserved,
            'reserved_until' => now()->subMinute(),
        ]);

    $grant = ($this->resgatar)('CLAU10');

    expect($grant->user_id)->toBe($this->user->getKey());
});
