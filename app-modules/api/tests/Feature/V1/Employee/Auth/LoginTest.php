<?php

declare(strict_types=1);

use App\Models\Users\User;
use Laravel\Sanctum\PersonalAccessToken;
use TresPontosTech\Company\Actions\AttachToDefaultCompany;
use TresPontosTech\Permissions\Roles;

use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;

/**
 * @return array{email: string, password: string, device_name: string}
 */
function loginCredentials(User $user, string $deviceName = 'Android', string $password = 'password'): array
{
    return ['email' => $user->email, 'password' => $password, 'device_name' => $deviceName];
}

it('signs an employee in and returns a device token with the profile', function (): void {
    $user = defaultCompanyUser();

    $response = postJson(route('api.v1.auth.login'), loginCredentials($user))
        ->assertOk()
        ->assertJsonStructure(['data' => ['token', 'user' => ['id', 'name', 'email', 'last_login_at', 'created_at']]])
        ->assertJsonPath('data.user.id', $user->id);

    $token = PersonalAccessToken::findToken($response->json('data.token'));

    expect($token)->not->toBeNull()
        ->and($token->tokenable->is($user))->toBeTrue()
        ->and($token->name)->toBe('Android')
        ->and($token->abilities)->toBe(['employee'])
        ->and($token->expires_at->toDateString())->toBe(now()->addDays(90)->toDateString())
        ->and($user->fresh()->last_login_at)->not->toBeNull()
        ->and($response->json('data.user.last_login_at'))->not->toBeNull();
});

it('lets a B2B employee sign in', function (): void {
    $employee = actingAsEmployee();
    auth()->logout();

    postJson(route('api.v1.auth.login'), loginCredentials($employee))
        ->assertOk()
        ->assertJsonPath('data.user.id', $employee->id);
});

it('matches the email regardless of case', function (string $stored, string $typed): void {
    $user = defaultCompanyUser(['email' => $stored]);

    postJson(route('api.v1.auth.login'), [...loginCredentials($user), 'email' => $typed])
        ->assertOk()
        ->assertJsonPath('data.user.id', $user->id);
})->with([
    'stored with capitals by the panel, sent lowercased by the app' => ['Maria.Silva@Example.com', 'maria.silva@example.com'],
    'stored lowercased, typed with capitals' => ['maria@example.com', 'Maria@Example.com'],
]);

it('issues a working token', function (): void {
    $user = defaultCompanyUser();

    $token = postJson(route('api.v1.auth.login'), loginCredentials($user))->json('data.token');

    getJson(route('api.v1.me.show'), ['Authorization' => 'Bearer ' . $token])
        ->assertOk()
        ->assertJsonPath('data.id', $user->id);
});

it('keeps a single token per device', function (): void {
    $user = defaultCompanyUser();

    postJson(route('api.v1.auth.login'), loginCredentials($user, 'Android'))->assertOk();
    postJson(route('api.v1.auth.login'), loginCredentials($user, 'Android'))->assertOk();
    postJson(route('api.v1.auth.login'), loginCredentials($user, 'iPhone'))->assertOk();

    expect($user->tokens()->pluck('name')->sort()->values()->all())->toBe(['Android', 'iPhone']);
});

it('rejects a wrong password on the email field', function (): void {
    $user = defaultCompanyUser();

    postJson(route('api.v1.auth.login'), loginCredentials($user, password: 'wrong-password'))
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['email' => 'E-mail ou senha incorretos.']);

    expect($user->tokens()->count())->toBe(0);
});

it('rejects an unknown email with the same message', function (): void {
    postJson(route('api.v1.auth.login'), ['email' => 'nobody@example.com', 'password' => 'password', 'device_name' => 'Web'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['email' => 'E-mail ou senha incorretos.']);
});

it('rejects accounts outside the app audience on the email field', function (User $user): void {
    postJson(route('api.v1.auth.login'), loginCredentials($user))
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['email' => 'Esta conta não tem acesso ao aplicativo.']);

    expect($user->tokens()->count())->toBe(0);
})->with([
    'without any company link' => fn (): User => User::factory()->create(),
    'consultant' => function (): User {
        $consultant = User::factory()->consultant()->create();
        resolve(AttachToDefaultCompany::class)->execute($consultant, Roles::Consultant);

        return $consultant;
    },
]);

it('validates the payload in Portuguese', function (): void {
    postJson(route('api.v1.auth.login'), ['email' => 'not-an-email'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['email', 'password', 'device_name'])
        ->assertJsonPath('errors.password.0', 'O campo senha é obrigatório.');
});

it('throttles the sixth attempt for the same email', function (): void {
    $user = defaultCompanyUser();

    foreach (range(1, 5) as $attempt) {
        postJson(route('api.v1.auth.login'), loginCredentials($user, password: 'wrong'))->assertUnprocessable();
    }

    postJson(route('api.v1.auth.login'), loginCredentials($user, password: 'wrong'))->assertTooManyRequests();
    postJson(route('api.v1.auth.login'), loginCredentials(defaultCompanyUser(), password: 'wrong'))->assertUnprocessable();
});

it('rejects an expired token', function (): void {
    $user = defaultCompanyUser();
    $token = postJson(route('api.v1.auth.login'), loginCredentials($user))->json('data.token');

    $this->travel(91)->days();

    getJson(route('api.v1.me.show'), ['Authorization' => 'Bearer ' . $token])
        ->assertUnauthorized();
});
