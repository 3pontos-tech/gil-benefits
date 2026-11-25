<?php

declare(strict_types=1);

use TresPontosTech\Discounts\Actions\RevokeDiscountGrant;
use TresPontosTech\Discounts\Enums\DiscountGrantRevocationReasonEnum;
use TresPontosTech\Discounts\Models\DiscountGrant;

it('grava a revogação com o motivo', function (): void {
    $grant = DiscountGrant::factory()->create();

    resolve(RevokeDiscountGrant::class)->handle($grant, DiscountGrantRevocationReasonEnum::Manual);

    expect($grant->fresh()->revoked_at)->not->toBeNull()
        ->and($grant->fresh()->revoked_reason)->toBe(DiscountGrantRevocationReasonEnum::Manual);
});

it('não sobrescreve uma revogação anterior', function (): void {
    $revogadoEm = now()->subDay()->startOfSecond();
    $grant = DiscountGrant::factory()->create([
        'revoked_at' => $revogadoEm,
        'revoked_reason' => DiscountGrantRevocationReasonEnum::Manual,
    ]);

    resolve(RevokeDiscountGrant::class)->handle($grant, DiscountGrantRevocationReasonEnum::TooManyAttempts);

    expect($grant->fresh()->revoked_at->equalTo($revogadoEm))->toBeTrue()
        ->and($grant->fresh()->revoked_reason)->toBe(DiscountGrantRevocationReasonEnum::Manual);
});
