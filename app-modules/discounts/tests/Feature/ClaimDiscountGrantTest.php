<?php

declare(strict_types=1);

use App\Models\Users\User;
use TresPontosTech\Discounts\Actions\ClaimDiscountGrant;
use TresPontosTech\Discounts\Models\DiscountGrant;
use TresPontosTech\Discounts\Support\TaxIdFingerprint;

beforeEach(function (): void {
    config(['discounts.tax_id_fingerprint_key' => 'clau-flamma-test-tax-key']);
});

function grantPendenteDaAna(): DiscountGrant
{
    return DiscountGrant::factory()->create([
        'email' => 'ana@example.com',
        'tax_id_fingerprint' => TaxIdFingerprint::make('52998224725'),
    ]);
}

it('resgata o grant com o e-mail do usuário e o CPF certo', function (): void {
    $grant = grantPendenteDaAna();
    $user = User::factory()->create(['email' => 'Ana@Example.com']);

    $resgatado = resolve(ClaimDiscountGrant::class)->handle($user, '529.982.247-25');

    expect($resgatado?->getKey())->toBe($grant->getKey())
        ->and($grant->fresh()->user_id)->toBe($user->getKey())
        ->and($grant->fresh()->claimed_at)->not->toBeNull()
        ->and($grant->fresh()->tax_id_fingerprint)->toBeNull();
});

it('não resgata com CPF errado e não altera o grant', function (): void {
    $grant = grantPendenteDaAna();
    $user = User::factory()->create(['email' => 'ana@example.com']);
    $fingerprintAntes = $grant->fresh()->tax_id_fingerprint;

    $resgatado = resolve(ClaimDiscountGrant::class)->handle($user, '11144477735');

    expect($resgatado)->toBeNull()
        ->and($grant->fresh()->user_id)->toBeNull()
        ->and($grant->fresh()->claimed_at)->toBeNull()
        ->and($grant->fresh()->tax_id_fingerprint)->toBe($fingerprintAntes);
});

it('não resgata grant vencido ou revogado', function (string $estado): void {
    $grant = DiscountGrant::factory()->{$estado}()->create([
        'email' => 'ana@example.com',
        'tax_id_fingerprint' => TaxIdFingerprint::make('52998224725'),
    ]);
    $user = User::factory()->create(['email' => 'ana@example.com']);

    $resgatado = resolve(ClaimDiscountGrant::class)->handle($user, '52998224725');

    expect($resgatado)->toBeNull()
        ->and($grant->fresh()->user_id)->toBeNull();
})->with(['vencido' => 'expired', 'revogado' => 'revoked']);

it('não resgata quando a chave do CPF não está configurada', function (): void {
    $grant = grantPendenteDaAna();
    $user = User::factory()->create(['email' => 'ana@example.com']);
    config(['discounts.tax_id_fingerprint_key' => null]);

    $resgatado = resolve(ClaimDiscountGrant::class)->handle($user, '52998224725');

    expect($resgatado)->toBeNull()
        ->and($grant->fresh()->user_id)->toBeNull();
});
