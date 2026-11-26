<?php

declare(strict_types=1);

use App\Models\Users\User;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use TresPontosTech\Discounts\Models\Discount;
use TresPontosTech\Discounts\Models\DiscountGrant;
use TresPontosTech\Discounts\Support\DiscountInviteUrl;

beforeEach(function (): void {
    config(['app.url' => 'https://flamma.test']);
    $this->grant = DiscountGrant::factory()->for(Discount::factory()->active())->create();
});

function convite(DiscountGrant $grant): string
{
    return URL::temporarySignedRoute(DiscountInviteUrl::ROUTE, $grant->expires_at, ['grant' => $grant->getKey()], absolute: false);
}

it('guarda o convite na sessão e manda para o cadastro', function (): void {
    $this->get(convite($this->grant))
        ->assertRedirect(route('filament.app.auth.register'))
        ->assertSessionHas(DiscountInviteUrl::SESSION_KEY, $this->grant->getKey());
});

it('aceita o link com UTMs e clau_id e grava o clau_id no cookie de atribuição', function (): void {
    $clauId = (string) Str::uuid();

    $response = $this->get(convite($this->grant) . '&' . http_build_query([
        'utm_source' => 'clau',
        'utm_medium' => 'whatsapp',
        'clau_id' => $clauId,
    ]));

    $response->assertRedirect(route('filament.app.auth.register'))
        ->assertSessionHas(DiscountInviteUrl::SESSION_KEY, $this->grant->getKey());

    expect(json_decode((string) $response->getCookie('signup_attribution')?->getValue(), true)['clau_id'] ?? null)->toBe($clauId);
});

it('recusa o link com a assinatura alterada', function (): void {
    $url = convite($this->grant);

    $this->get($url . 'x')
        ->assertRedirect(route('filament.app.auth.register'))
        ->assertSessionMissing(DiscountInviteUrl::SESSION_KEY);
});

it('recusa o link depois do vencimento', function (): void {
    $url = convite($this->grant);

    $this->travelTo($this->grant->expires_at->addSecond());

    $this->get($url)
        ->assertRedirect(route('filament.app.auth.register'))
        ->assertSessionMissing(DiscountInviteUrl::SESSION_KEY);
});

it('manda para o login quando o convite já foi resgatado', function (): void {
    $this->grant->update(['user_id' => User::factory()->create()->getKey(), 'claimed_at' => now()]);

    $this->get(convite($this->grant))
        ->assertRedirect(route('filament.app.auth.login'))
        ->assertSessionMissing(DiscountInviteUrl::SESSION_KEY);
});

it('recusa o convite revogado e não guarda a chave', function (): void {
    $this->grant->update(['revoked_at' => now()]);

    $this->get(convite($this->grant))
        ->assertRedirect(route('filament.app.auth.register'))
        ->assertSessionMissing(DiscountInviteUrl::SESSION_KEY);
});

it('apaga a chave do convite quando a pessoa segue sem o desconto', function (): void {
    $this->withSession([DiscountInviteUrl::SESSION_KEY => $this->grant->getKey()])
        ->get(route(DiscountInviteUrl::LEAVE_ROUTE))
        ->assertRedirect(route('filament.app.auth.register'))
        ->assertSessionMissing(DiscountInviteUrl::SESSION_KEY);
});
