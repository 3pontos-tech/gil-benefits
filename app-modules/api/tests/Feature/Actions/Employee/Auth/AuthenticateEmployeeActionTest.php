<?php

declare(strict_types=1);

use App\Models\Users\User;
use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\PersonalAccessToken;
use TresPontosTech\Api\Actions\Employee\Auth\AuthenticateEmployeeAction;
use TresPontosTech\Api\Exceptions\Employee\Auth\EmployeeLoginException;

it('returns the user and a plain text token for the device', function (): void {
    Event::fake([Login::class]);
    $user = standaloneSubscriber();

    $session = resolve(AuthenticateEmployeeAction::class)->handle($user->email, 'password', 'Android');

    expect($session->user->is($user))->toBeTrue()
        ->and(PersonalAccessToken::findToken($session->plainTextToken)?->name)->toBe('Android');

    Event::assertDispatched(Login::class, fn (Login $event): bool => $event->guard === 'sanctum' && $event->user->is($user));
});

it('does not tell an unknown email from a wrong password', function (?string $email): void {
    $user = standaloneSubscriber();

    resolve(AuthenticateEmployeeAction::class)->handle($email ?? $user->email, 'wrong-password', 'Android');
})->with([
    'unknown email' => 'nobody@example.com',
    'wrong password' => null,
])->throws(EmployeeLoginException::class, 'E-mail ou senha incorretos.');

it('refuses an account outside the app audience without issuing a token', function (): void {
    $user = User::factory()->create();

    expect(fn () => resolve(AuthenticateEmployeeAction::class)->handle($user->email, 'password', 'Android'))
        ->toThrow(EmployeeLoginException::class, 'Esta conta não tem acesso ao aplicativo.');

    expect($user->tokens()->count())->toBe(0);
});

it('rehashes a password stored with a lower bcrypt cost than configured', function (): void {
    $user = standaloneSubscriber();
    $user->forceFill(['password' => bcrypt('password', ['rounds' => 4])])->save();

    config(['hashing.bcrypt.rounds' => 10]);
    Hash::forgetDrivers();

    resolve(AuthenticateEmployeeAction::class)->handle($user->email, 'password', 'Android');

    $password = $user->refresh()->password;

    expect(password_get_info($password)['options']['cost'])->toBe(10)
        ->and(Hash::check('password', $password))->toBeTrue();
});

it('keeps a password already stored with the configured bcrypt cost', function (): void {
    config(['hashing.bcrypt.rounds' => 10]);
    Hash::forgetDrivers();

    $user = standaloneSubscriber();
    $user->forceFill(['password' => bcrypt('password')])->save();
    $password = $user->password;

    resolve(AuthenticateEmployeeAction::class)->handle($user->email, 'password', 'Android');

    expect($user->refresh()->password)->toBe($password);
});

it('does not rehash the password when the login fails', function (string $password, bool $outsideAudience): void {
    $user = $outsideAudience ? User::factory()->create() : standaloneSubscriber();
    $user->forceFill(['password' => bcrypt('password', ['rounds' => 4])])->save();
    $hash = $user->password;

    config(['hashing.bcrypt.rounds' => 10]);
    Hash::forgetDrivers();

    expect(fn () => resolve(AuthenticateEmployeeAction::class)->handle($user->email, $password, 'Android'))
        ->toThrow(EmployeeLoginException::class);

    expect($user->refresh()->password)->toBe($hash);
})->with([
    'wrong password' => ['wrong-password', false],
    'account outside the app audience' => ['password', true],
]);
