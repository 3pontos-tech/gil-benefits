<?php

declare(strict_types=1);

use App\Models\Users\User;
use TresPontosTech\Discounts\Actions\VerifyDiscountGrantTaxId;
use TresPontosTech\Discounts\Enums\DiscountGrantRevocationReasonEnum;
use TresPontosTech\Discounts\Enums\DiscountTaxIdCheckEnum;
use TresPontosTech\Discounts\Models\DiscountGrant;

function grantPendenteComCpf(): DiscountGrant
{
    return DiscountGrant::factory()->create(['tax_id' => '52998224725']);
}

it('confere o CPF certo', function (): void {
    $grant = grantPendenteComCpf();

    $resultado = resolve(VerifyDiscountGrantTaxId::class)->handle($grant, '52998224725');

    expect($resultado)->toBe(DiscountTaxIdCheckEnum::Matches);
});

it('registra a tentativa quando o CPF está errado', function (): void {
    $grant = grantPendenteComCpf();

    $resultado = resolve(VerifyDiscountGrantTaxId::class)->handle($grant, '11144477735');

    expect($resultado)->toBe(DiscountTaxIdCheckEnum::Mismatch)
        ->and($grant->fresh()->failed_tax_id_attempts)->toBe(1);
});

it('bloqueia e revoga o grant na quinta tentativa errada', function (): void {
    $grant = grantPendenteComCpf();
    $verificar = resolve(VerifyDiscountGrantTaxId::class);

    $resultados = collect(range(1, VerifyDiscountGrantTaxId::MAX_ATTEMPTS))
        ->map(fn (): DiscountTaxIdCheckEnum => $verificar->handle($grant, '11144477735'))
        ->all();

    expect($resultados)->toBe([
        DiscountTaxIdCheckEnum::Mismatch,
        DiscountTaxIdCheckEnum::Mismatch,
        DiscountTaxIdCheckEnum::Mismatch,
        DiscountTaxIdCheckEnum::Mismatch,
        DiscountTaxIdCheckEnum::Locked,
    ])->and($grant->fresh()->revoked_at)->not->toBeNull()
        ->and($grant->fresh()->revoked_reason)->toBe(DiscountGrantRevocationReasonEnum::TooManyAttempts);
});

it('bloqueia a conferência de grant já resgatado', function (): void {
    $user = User::factory()->create();
    $grant = DiscountGrant::factory()->claimedBy($user)->create(['tax_id' => '52998224725']);

    $resultado = resolve(VerifyDiscountGrantTaxId::class)->handle($grant, '52998224725');

    expect($resultado)->toBe(DiscountTaxIdCheckEnum::Locked);
});
