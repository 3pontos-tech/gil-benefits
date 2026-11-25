<?php

declare(strict_types=1);

use App\Models\Users\User;
use TresPontosTech\Discounts\Actions\ClaimDiscountGrant;
use TresPontosTech\Discounts\Models\DiscountGrant;

function grantPendenteDaAna(): DiscountGrant
{
    return DiscountGrant::factory()->create([
        'email' => 'ana@example.com',
        'tax_id' => '52998224725',
        'phone_number' => '5511999999999',
    ]);
}

it('resgata o grant com o e-mail do usuário e o CPF certo', function (): void {
    $grant = grantPendenteDaAna();
    $user = User::factory()->create(['email' => 'Ana@Example.com']);

    $resgatado = resolve(ClaimDiscountGrant::class)->handle($user, '52998224725');

    expect($resgatado?->getKey())->toBe($grant->getKey())
        ->and($grant->fresh())
        ->user_id->toBe($user->getKey())
        ->claimed_at->not->toBeNull();
});

it('zera o CPF e o telefone do grant depois do resgate, mantendo o telefone para o cadastro', function (): void {
    $grant = grantPendenteDaAna();
    $user = User::factory()->create(['email' => 'ana@example.com']);

    $resgatado = resolve(ClaimDiscountGrant::class)->handle($user, '52998224725');

    expect($grant->fresh())
        ->tax_id->toBeNull()
        ->phone_number->toBeNull()
        ->and($resgatado?->phone_number)->toBe('5511999999999');
});

it('não resgata com CPF errado e não altera o grant', function (): void {
    $grant = grantPendenteDaAna();
    $user = User::factory()->create(['email' => 'ana@example.com']);

    $resgatado = resolve(ClaimDiscountGrant::class)->handle($user, '11144477735');

    expect($resgatado)->toBeNull()
        ->and($grant->fresh())
        ->user_id->toBeNull()
        ->claimed_at->toBeNull()
        ->tax_id->toBe('52998224725')
        ->phone_number->toBe('5511999999999');
});

it('não resgata grant vencido ou revogado', function (string $estado): void {
    $grant = DiscountGrant::factory()->{$estado}()->create([
        'email' => 'ana@example.com',
        'tax_id' => '52998224725',
    ]);
    $user = User::factory()->create(['email' => 'ana@example.com']);

    $resgatado = resolve(ClaimDiscountGrant::class)->handle($user, '52998224725');

    expect($resgatado)->toBeNull()
        ->and($grant->fresh()->user_id)->toBeNull();
})->with(['vencido' => 'expired', 'revogado' => 'revoked']);
