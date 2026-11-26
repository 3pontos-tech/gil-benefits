<?php

declare(strict_types=1);

use App\Models\Users\Detail;
use App\Models\Users\User;
use Illuminate\Support\Facades\Date;
use TresPontosTech\Discounts\Actions\ReserveDiscountGrant;
use TresPontosTech\Discounts\DTOs\DiscountReservationOutcome;
use TresPontosTech\Discounts\Enums\DiscountIneligibilityReasonEnum;
use TresPontosTech\Discounts\Enums\DiscountReservationStatusEnum;
use TresPontosTech\Discounts\Models\Discount;
use TresPontosTech\Discounts\Models\DiscountGrant;

function reservarDescontoClau(Discount $discount, string $externalRef = 'clau-1', string $email = 'Ana@Example.com', string $cpf = '52998224725'): DiscountReservationOutcome
{
    return resolve(ReserveDiscountGrant::class)->handle(
        discount: $discount,
        source: 'clau',
        externalRef: $externalRef,
        name: 'Ana Souza',
        email: $email,
        taxId: $cpf,
        phoneNumber: '+5511999999999',
        consentTermVersion: '1',
        consentAcceptedAt: now(),
    );
}

it('cria a reserva com o e-mail normalizado e validade de 180 dias', function (): void {
    Date::setTestNow(Date::parse('2026-11-25 10:00:00'));
    $discount = Discount::factory()->active()->create();

    $outcome = reservarDescontoClau($discount);

    expect($outcome->status)->toBe(DiscountReservationStatusEnum::Reserved)
        ->and($outcome->created)->toBeTrue()
        ->and($outcome->grant->email)->toBe('ana@example.com')
        ->and($outcome->grant->expires_at->equalTo(now()->addDays(180)))->toBeTrue();
});

it('limita a validade da concessão ao fim do cupom quando ele termina antes do prazo', function (): void {
    Date::setTestNow(Date::parse('2026-11-25 10:00:00'));
    $discount = Discount::factory()->active()->create(['ends_at' => now()->addDays(20)]);

    $outcome = reservarDescontoClau($discount);

    expect($outcome->grant->expires_at->equalTo(now()->addDays(20)))->toBeTrue();
});

it('não concede cupom fora da janela absoluta', function (Closure $janela): void {
    $discount = Discount::factory()->active()->create($janela());

    $outcome = reservarDescontoClau($discount);

    expect($outcome->status)->toBe(DiscountReservationStatusEnum::Ineligible)
        ->and($outcome->reason)->toBe(DiscountIneligibilityReasonEnum::DiscountInactive)
        ->and(DiscountGrant::query()->count())->toBe(0);
})->with([
    'ainda não começou' => [fn (): array => ['starts_at' => now()->addDay()]],
    'já terminou' => [fn (): array => ['ends_at' => now()->subDay()]],
]);

it('devolve a mesma reserva sem duplicar quando o external_ref e o e-mail se repetem', function (): void {
    $discount = Discount::factory()->active()->create();
    $primeira = reservarDescontoClau($discount);

    $repeticao = reservarDescontoClau($discount);

    expect($repeticao->status)->toBe(DiscountReservationStatusEnum::Reserved)
        ->and($repeticao->created)->toBeFalse()
        ->and($repeticao->grant->getKey())->toBe($primeira->grant->getKey())
        ->and(DiscountGrant::query()->count())->toBe(1);
});

it('recusa o mesmo external_ref com outro e-mail como conflito', function (): void {
    $discount = Discount::factory()->active()->create();
    reservarDescontoClau($discount);

    $outcome = reservarDescontoClau($discount, email: 'bruno@example.com');

    expect($outcome->status)->toBe(DiscountReservationStatusEnum::Conflict);
});

it('recusa desconto inativo', function (): void {
    $discount = Discount::factory()->inactive()->create();

    $outcome = reservarDescontoClau($discount);

    expect($outcome->status)->toBe(DiscountReservationStatusEnum::Ineligible)
        ->and($outcome->reason)->toBe(DiscountIneligibilityReasonEnum::DiscountInactive);
});

it('recusa e-mail de usuário existente', function (): void {
    $discount = Discount::factory()->active()->create();
    User::factory()->create(['email' => 'ana@example.com']);

    $outcome = reservarDescontoClau($discount);

    expect($outcome->reason)->toBe(DiscountIneligibilityReasonEnum::ExistingAccount);
});

it('recusa e-mail de usuário apagado', function (): void {
    $discount = Discount::factory()->active()->create();
    User::factory()->create(['email' => 'ana@example.com'])->delete();

    $outcome = reservarDescontoClau($discount);

    expect($outcome->reason)->toBe(DiscountIneligibilityReasonEnum::ExistingAccount);
});

it('recusa CPF já cadastrado em um detalhe de usuário, com ou sem máscara', function (string $taxIdCadastrado): void {
    $discount = Discount::factory()->active()->create();
    Detail::factory()->create(['tax_id' => $taxIdCadastrado]);

    $outcome = reservarDescontoClau($discount);

    expect($outcome->reason)->toBe(DiscountIneligibilityReasonEnum::ExistingAccount);
})->with(['sem máscara' => ['52998224725'], 'com máscara' => ['529.982.247-25']]);

it('recusa CPF de detalhe apagado', function (): void {
    $discount = Discount::factory()->active()->create();
    Detail::factory()->create(['tax_id' => '52998224725'])->delete();

    $outcome = reservarDescontoClau($discount);

    expect($outcome->reason)->toBe(DiscountIneligibilityReasonEnum::ExistingAccount);
});

it('recusa outra reserva do mesmo desconto com o mesmo e-mail', function (): void {
    $discount = Discount::factory()->active()->create();
    reservarDescontoClau($discount);

    $outcome = reservarDescontoClau($discount, externalRef: 'clau-2', cpf: '11144477735');

    expect($outcome->reason)->toBe(DiscountIneligibilityReasonEnum::AlreadyGranted);
});

it('guarda o CPF e o telefone na reserva', function (): void {
    $discount = Discount::factory()->active()->create();

    $grant = reservarDescontoClau($discount)->grant;

    expect($grant->fresh())
        ->tax_id->toBe('52998224725')
        ->phone_number->toBe('+5511999999999');

    expect($grant->fresh()->getRawOriginal('tax_id'))
        ->not->toBe('52998224725')
        ->not->toContain('52998224725')
        ->and(decrypt($grant->fresh()->getRawOriginal('tax_id'), false))->toBe('52998224725');
});

it('recusa a repetição de um grant revogado', function (): void {
    $discount = Discount::factory()->active()->create();
    DiscountGrant::factory()->for($discount)->revoked()->create([
        'external_ref' => 'clau-1',
        'email' => 'ana@example.com',
    ]);

    $outcome = reservarDescontoClau($discount);

    expect($outcome->reason)->toBe(DiscountIneligibilityReasonEnum::Revoked);
});

it('recusa a repetição de um grant já resgatado', function (): void {
    $discount = Discount::factory()->active()->create();
    $user = User::factory()->create(['email' => 'ana@example.com']);
    DiscountGrant::factory()->for($discount)->claimedBy($user)->create(['external_ref' => 'clau-1']);

    $outcome = reservarDescontoClau($discount);

    expect($outcome->reason)->toBe(DiscountIneligibilityReasonEnum::AlreadyClaimed);
});

it('recusa a repetição de um grant vencido', function (): void {
    $discount = Discount::factory()->active()->create();
    DiscountGrant::factory()->for($discount)->expired()->create([
        'external_ref' => 'clau-1',
        'email' => 'ana@example.com',
    ]);

    $outcome = reservarDescontoClau($discount);

    expect($outcome->reason)->toBe(DiscountIneligibilityReasonEnum::Expired);
});
