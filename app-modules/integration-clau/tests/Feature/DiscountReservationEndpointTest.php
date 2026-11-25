<?php

declare(strict_types=1);

use App\Models\Users\Detail;
use App\Models\Users\User;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Notification;
use TresPontosTech\Discounts\Models\Discount;
use TresPontosTech\Discounts\Models\DiscountGrant;
use TresPontosTech\Discounts\Notifications\DiscountGrantReservedNotification;

beforeEach(function (): void {
    config([
        'clau.signing_secret' => 'clau-flamma-test-signing',
        'app.url' => 'https://flamma.test',
    ]);

    Discount::query()->where('slug', 'clau-diagnostico')->update(['active' => true]);

    Notification::fake();
});

function postSignedReservation(array $payload, ?int $timestamp = null, string $secret = 'clau-flamma-test-signing')
{
    $json = json_encode($payload);
    $ts = (string) ($timestamp ?? now()->getTimestamp());
    $sig = hash_hmac('sha256', $ts . '.' . $json, $secret);

    return test()->call('POST', '/api/clau/v1/discount-reservations', [], [], [], [
        'CONTENT_TYPE' => 'application/json',
        'HTTP_ACCEPT' => 'application/json',
        'HTTP_X_CLAU_TIMESTAMP' => $ts,
        'HTTP_X_CLAU_SIGNATURE' => $sig,
    ], $json);
}

function reservationPayload(array $overrides = []): array
{
    return array_merge([
        'clau_id' => '0196f0a2-0000-7000-8000-000000000001',
        'name' => 'Ana Souza',
        'email' => 'ana@example.com',
        'tax_id' => '52998224725',
        'phone' => '5511999999999',
        'consent' => ['term_version' => '1', 'accepted_at' => now()->toIso8601String()],
    ], $overrides);
}

it('aceita a assinatura do vetor fixo e segue para a validação', function (): void {
    $this->travelTo(Date::createFromTimestamp(1764072000));

    $json = '{"clau_id":"0196f0a2-0000-7000-8000-000000000001"}';
    $response = $this->call('POST', '/api/clau/v1/discount-reservations', [], [], [], [
        'CONTENT_TYPE' => 'application/json',
        'HTTP_ACCEPT' => 'application/json',
        'HTTP_X_CLAU_TIMESTAMP' => '1764072000',
        'HTTP_X_CLAU_SIGNATURE' => '301912dd5c4b42b470a5bed3065430963f9f497cae7cea99b39e2c1050e10c7d',
    ], $json);

    $response->assertUnprocessable();
});

it('recusa assinatura errada com 401', function (): void {
    postSignedReservation(reservationPayload(), secret: 'outro-segredo')->assertUnauthorized();
});

it('recusa timestamp com 301 segundos de diferença com 401', function (): void {
    postSignedReservation(reservationPayload(), now()->getTimestamp() - 301)->assertUnauthorized();
});

it('recusa requisição sem cabeçalhos com 401', function (): void {
    $this->postJson('/api/clau/v1/discount-reservations', reservationPayload())->assertUnauthorized();
});

it('responde 503 quando o segredo não está configurado', function (): void {
    config(['clau.signing_secret' => '']);

    postSignedReservation(reservationPayload())->assertServiceUnavailable();
});

it('cria a reserva com 201, o convite e o e-mail de ativação', function (): void {
    $response = postSignedReservation(reservationPayload());

    $response->assertCreated()
        ->assertJsonPath('status', 'reserved')
        ->assertJsonPath('invite_url', fn (string $url): bool => str_starts_with($url, 'https://flamma.test/convite-clau/'));

    $grant = DiscountGrant::query()->sole();

    expect($response->json('reservation_id'))->toBe($grant->getKey());

    Notification::assertSentOnDemand(
        DiscountGrantReservedNotification::class,
        fn ($notification, $channels, $notifiable): bool => $notifiable->routes['mail'] === 'ana@example.com',
    );
});

it('guarda o CPF só com os dígitos e o telefone da reserva', function (): void {
    postSignedReservation(reservationPayload(['tax_id' => '529.982.247-25']))->assertCreated();

    expect(DiscountGrant::query()->sole())
        ->tax_id->toBe('52998224725')
        ->phone_number->toBe('5511999999999');
});

it('aceita a reserva sem telefone', function (): void {
    $payload = reservationPayload();
    unset($payload['phone']);

    postSignedReservation($payload)->assertCreated();

    expect(DiscountGrant::query()->sole()->phone_number)->toBeNull();
});

it('devolve a mesma reserva sem duplicar nem reenviar o e-mail na repetição', function (): void {
    $primeira = postSignedReservation(reservationPayload())->assertCreated()->json();

    $repeticao = postSignedReservation(reservationPayload())->assertOk()->json();

    expect($repeticao['reservation_id'])->toBe($primeira['reservation_id'])
        ->and(DiscountGrant::query()->count())->toBe(1);

    Notification::assertSentOnDemandTimes(DiscountGrantReservedNotification::class, 1);
});

it('é inelegível quando já existe conta com o e-mail', function (): void {
    User::factory()->create(['email' => 'ana@example.com']);

    postSignedReservation(reservationPayload())
        ->assertOk()
        ->assertExactJson(['status' => 'ineligible', 'reason' => 'existing_account']);
});

it('é inelegível quando já existe cadastro com o mesmo CPF, com ou sem máscara', function (string $taxIdCadastrado): void {
    Detail::factory()->create(['tax_id' => $taxIdCadastrado]);

    postSignedReservation(reservationPayload())
        ->assertOk()
        ->assertExactJson(['status' => 'ineligible', 'reason' => 'existing_account']);
})->with(['sem máscara' => ['52998224725'], 'com máscara' => ['529.982.247-25']]);

it('responde 409 quando o mesmo clau_id chega com outro e-mail', function (): void {
    postSignedReservation(reservationPayload())->assertCreated();

    postSignedReservation(reservationPayload(['email' => 'outra@example.com']))
        ->assertConflict()
        ->assertExactJson(['status' => 'conflict']);
});

it('é inelegível como já concedido quando outro clau_id chega com o mesmo e-mail', function (): void {
    postSignedReservation(reservationPayload())->assertCreated();

    postSignedReservation(reservationPayload(['clau_id' => '0196f0a2-0000-7000-8000-000000000002']))
        ->assertOk()
        ->assertExactJson(['status' => 'ineligible', 'reason' => 'already_granted']);
});

it('é inelegível quando o desconto está inativo', function (): void {
    Discount::query()->where('slug', 'clau-diagnostico')->update(['active' => false]);

    postSignedReservation(reservationPayload())
        ->assertOk()
        ->assertExactJson(['status' => 'ineligible', 'reason' => 'discount_inactive']);
});

it('responde 422 quando o CPF é inválido', function (): void {
    $response = postSignedReservation(reservationPayload(['tax_id' => '12345678900']));

    $response->assertUnprocessable()
        ->assertJsonPath('status', 'invalid');

    expect($response->json('errors'))->toHaveKey('tax_id');
});
