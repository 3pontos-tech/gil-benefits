<?php

declare(strict_types=1);

use Illuminate\Contracts\Auth\Factory;

use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;

it('revokes only the token of the device that signed out', function (): void {
    $user = defaultCompanyUser();

    $phone = $user->createToken('Android', ['employee'])->plainTextToken;
    $tablet = $user->createToken('iPad', ['employee'])->plainTextToken;

    postJson(route('api.v1.auth.logout'), headers: ['Authorization' => 'Bearer ' . $phone])
        ->assertNoContent();

    resolve(Factory::class)->forgetGuards();

    getJson(route('api.v1.me.show'), ['Authorization' => 'Bearer ' . $phone])->assertUnauthorized();

    resolve(Factory::class)->forgetGuards();

    getJson(route('api.v1.me.show'), ['Authorization' => 'Bearer ' . $tablet])->assertOk();

    expect($user->tokens()->pluck('name')->all())->toBe(['iPad']);
});

it('requires a token to sign out', function (): void {
    postJson(route('api.v1.auth.logout'))->assertUnauthorized();
});
