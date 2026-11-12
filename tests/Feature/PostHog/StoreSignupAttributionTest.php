<?php

declare(strict_types=1);

use App\Http\Middleware\StoreSignupAttribution;
use Illuminate\Support\Str;

it('keeps the Flare id and the UTMs of the invite until the signup', function (): void {
    $flareId = (string) Str::uuid();

    $this->get(route('site.home', [
        'utm_source' => 'flare',
        'utm_medium' => 'whatsapp',
        'utm_campaign' => 'gancho_pos_relatorio',
        'utm_content' => 'variante_a',
        'flare_id' => $flareId,
    ]))->assertOk();

    $this->get(route('site.companies'))->assertOk();

    expect(session(StoreSignupAttribution::FLARE_ID))->toBe($flareId)
        ->and(session(StoreSignupAttribution::UTMS))->toBe([
            'utm_source' => 'flare',
            'utm_medium' => 'whatsapp',
            'utm_campaign' => 'gancho_pos_relatorio',
            'utm_content' => 'variante_a',
        ]);
});

it('keeps the UTMs of the first touch', function (): void {
    $this->get(route('site.home', ['utm_source' => 'instagram', 'utm_campaign' => 'lancamento']))->assertOk();
    $this->get(route('site.collaborator', ['utm_source' => 'google', 'utm_term' => 'beneficio']))->assertOk();

    expect(session(StoreSignupAttribution::UTMS))->toBe(['utm_source' => 'instagram', 'utm_campaign' => 'lancamento']);
});

it('ignores a Flare id that is not a UUID', function (mixed $flareId): void {
    $this->get(route('site.home', ['flare_id' => $flareId]))->assertOk();

    expect(session()->has(StoreSignupAttribution::FLARE_ID))->toBeFalse();
})->with(['text' => 'nao-e-uuid', 'number' => '123', 'array' => [['a']]]);

it('stores nothing for a visit without attribution', function (): void {
    $this->get(route('site.home'))->assertOk();

    expect(session()->has(StoreSignupAttribution::FLARE_ID))->toBeFalse()
        ->and(session()->has(StoreSignupAttribution::UTMS))->toBeFalse();
});

it('also keeps the attribution of a link that opens the signup page directly', function (): void {
    $flareId = (string) Str::uuid();

    $this->get(route('filament.app.auth.register', ['flare_id' => $flareId, 'utm_source' => 'flare']))->assertOk();

    expect(session(StoreSignupAttribution::FLARE_ID))->toBe($flareId)
        ->and(session(StoreSignupAttribution::UTMS))->toBe(['utm_source' => 'flare']);
});
