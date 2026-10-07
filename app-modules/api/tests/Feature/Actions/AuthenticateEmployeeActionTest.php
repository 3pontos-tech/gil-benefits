<?php

declare(strict_types=1);

use App\Models\Users\User;
use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Event;
use Laravel\Sanctum\PersonalAccessToken;
use TresPontosTech\Api\Actions\V1\Employee\AuthenticateEmployeeAction;
use TresPontosTech\Api\Exceptions\EmployeeLoginException;

it('returns the user and a plain text token for the device', function (): void {
    Event::fake([Login::class]);
    $user = defaultCompanyUser();

    $session = resolve(AuthenticateEmployeeAction::class)->handle($user->email, 'password', 'Android');

    expect($session->user->is($user))->toBeTrue()
        ->and(PersonalAccessToken::findToken($session->plainTextToken)?->name)->toBe('Android');

    Event::assertDispatched(Login::class, fn (Login $event): bool => $event->guard === 'sanctum' && $event->user->is($user));
});

it('does not tell an unknown email from a wrong password', function (?string $email): void {
    $user = defaultCompanyUser();

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
