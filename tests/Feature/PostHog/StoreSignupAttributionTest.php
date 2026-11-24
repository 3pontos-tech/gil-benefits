<?php

declare(strict_types=1);

use App\Http\Middleware\StoreSignupAttribution;
use Illuminate\Support\Str;

it('keeps the Clau id and the UTMs of the invite in a 30 day cookie', function (): void {
    $clauId = (string) Str::uuid();

    $response = $this->get(route('site.home', [
        'utm_source' => 'clau',
        'utm_medium' => 'whatsapp',
        'utm_campaign' => 'gancho_pos_relatorio',
        'utm_content' => 'variante_a',
        'clau_id' => $clauId,
    ]))->assertOk();

    $response->assertCookie(StoreSignupAttribution::COOKIE, json_encode([
        'clau_id' => $clauId,
        'utms' => [
            'utm_source' => 'clau',
            'utm_medium' => 'whatsapp',
            'utm_campaign' => 'gancho_pos_relatorio',
            'utm_content' => 'variante_a',
        ],
    ]));

    $cookie = collect($response->headers->getCookies())->firstWhere(fn ($cookie): bool => $cookie->getName() === StoreSignupAttribution::COOKIE);

    expect($cookie->getExpiresTime())->toBeGreaterThan(now()->addDays(29)->getTimestamp());
});

it('keeps the UTMs of the first touch and the Clau id of the latest invite', function (): void {
    $latestClauId = (string) Str::uuid();

    $this->withCookie(StoreSignupAttribution::COOKIE, json_encode([
        'clau_id' => (string) Str::uuid(),
        'utms' => ['utm_source' => 'ig', 'utm_campaign' => 'flamma_lancamento_2026_11'],
    ]))
        ->get(route('site.collaborator', ['utm_source' => 'google', 'clau_id' => $latestClauId]))
        ->assertOk()
        ->assertCookie(StoreSignupAttribution::COOKIE, json_encode([
            'clau_id' => $latestClauId,
            'utms' => ['utm_source' => 'ig', 'utm_campaign' => 'flamma_lancamento_2026_11'],
        ]));
});

it('leaves the cookie untouched when the visit brings nothing new', function (): void {
    $this->withCookie(StoreSignupAttribution::COOKIE, json_encode(['utms' => ['utm_source' => 'ig']]))
        ->get(route('site.home', ['utm_source' => 'google']))
        ->assertOk()
        ->assertCookieMissing(StoreSignupAttribution::COOKIE);
});

it('ignores a Clau id that is not a UUID', function (mixed $clauId): void {
    $this->get(route('site.home', ['clau_id' => $clauId]))
        ->assertOk()
        ->assertCookieMissing(StoreSignupAttribution::COOKIE);
})->with(['text' => 'nao-e-uuid', 'number' => '123', 'array' => [['a']]]);

it('stores nothing for a visit without attribution', function (): void {
    $this->get(route('site.home'))
        ->assertOk()
        ->assertCookieMissing(StoreSignupAttribution::COOKIE);
});

it('also keeps the attribution of a link that opens the signup page directly', function (): void {
    $clauId = (string) Str::uuid();

    $this->get(route('filament.app.auth.register', ['clau_id' => $clauId, 'utm_source' => 'clau']))
        ->assertOk()
        ->assertCookie(StoreSignupAttribution::COOKIE, json_encode(['clau_id' => $clauId, 'utms' => ['utm_source' => 'clau']]));
});

it('discards a tampered cookie with an invalid Clau id', function (): void {
    $this->withCookie(StoreSignupAttribution::COOKIE, json_encode(['clau_id' => 'nao-e-uuid', 'utms' => ['utm_source' => ['x']]]))
        ->get(route('site.home', ['utm_source' => 'ig']))
        ->assertOk()
        ->assertCookie(StoreSignupAttribution::COOKIE, json_encode(['utms' => ['utm_source' => 'ig']]));
});
