<?php

declare(strict_types=1);

use App\Models\Users\User;
use TresPontosTech\Discounts\Enums\DiscountGrantStatusEnum;
use TresPontosTech\Discounts\Models\DiscountGrant;

it('informa o status de cada estado da concessão', function (): void {
    $user = User::factory()->create();

    expect(DiscountGrant::factory()->create()->status())->toBe(DiscountGrantStatusEnum::Reserved)
        ->and(DiscountGrant::factory()->claimedBy($user)->create()->status())->toBe(DiscountGrantStatusEnum::Claimed)
        ->and(DiscountGrant::factory()->revoked()->create()->status())->toBe(DiscountGrantStatusEnum::Revoked)
        ->and(DiscountGrant::factory()->expired()->create()->status())->toBe(DiscountGrantStatusEnum::Expired);
});

it('traz só a reserva pendente no escopo pending', function (): void {
    $user = User::factory()->create();
    $reserved = DiscountGrant::factory()->create();

    DiscountGrant::factory()->claimedBy($user)->create();
    DiscountGrant::factory()->revoked()->create();
    DiscountGrant::factory()->expired()->create();

    expect(DiscountGrant::query()->pending()->pluck('id')->all())->toBe([$reserved->getKey()])
        ->and($reserved->isPending())->toBeTrue();
});

it('apaga a reserva vencida sem usuário e mantém as demais no model:prune', function (): void {
    $user = User::factory()->create();
    $expiredReserve = DiscountGrant::factory()->expired()->create();
    $validReserve = DiscountGrant::factory()->create();
    $claimedAndExpired = DiscountGrant::factory()->claimedBy($user)->expired()->create();

    $this->artisan('model:prune', ['--model' => [DiscountGrant::class]])->assertSuccessful();

    expect(DiscountGrant::query()->whereKey($expiredReserve->getKey())->exists())->toBeFalse()
        ->and(DiscountGrant::query()->whereKey($validReserve->getKey())->exists())->toBeTrue()
        ->and(DiscountGrant::query()->whereKey($claimedAndExpired->getKey())->exists())->toBeTrue();
});
