<?php

declare(strict_types=1);

use Illuminate\Contracts\Auth\Factory;
use Illuminate\Support\Facades\Hash;

use function Pest\Laravel\getJson;
use function Pest\Laravel\putJson;

it('changes the password and signs the other devices out', function (): void {
    $user = defaultCompanyUser();
    $phone = $user->createToken('Android', ['employee'])->plainTextToken;
    $tablet = $user->createToken('iPad', ['employee'])->plainTextToken;

    putJson(route('api.v1.me.password'), [
        'current_password' => 'password',
        'password' => 'nova-senha-123',
        'password_confirmation' => 'nova-senha-123',
    ], ['Authorization' => 'Bearer ' . $phone])
        ->assertNoContent();

    expect(Hash::check('nova-senha-123', $user->fresh()->password))->toBeTrue()
        ->and($user->tokens()->pluck('name')->all())->toBe(['Android']);

    resolve(Factory::class)->forgetGuards();
    getJson(route('api.v1.me.show'), ['Authorization' => 'Bearer ' . $tablet])->assertUnauthorized();

    resolve(Factory::class)->forgetGuards();
    getJson(route('api.v1.me.show'), ['Authorization' => 'Bearer ' . $phone])->assertOk();
});

it('rejects a wrong current password', function (): void {
    $employee = actingAsApiEmployee();

    putJson(route('api.v1.me.password'), [
        'current_password' => 'wrong-password',
        'password' => 'nova-senha-123',
        'password_confirmation' => 'nova-senha-123',
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['current_password']);

    expect(Hash::check('password', $employee->fresh()->password))->toBeTrue();
});

it('requires the confirmation to match', function (): void {
    actingAsApiEmployee();

    putJson(route('api.v1.me.password'), [
        'current_password' => 'password',
        'password' => 'nova-senha-123',
        'password_confirmation' => 'outra-senha-123',
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['password' => 'A confirmação do campo nova senha não corresponde.']);
});
