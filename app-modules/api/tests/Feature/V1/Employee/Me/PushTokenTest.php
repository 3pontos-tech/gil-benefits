<?php

declare(strict_types=1);

use App\Models\Users\User;
use Illuminate\Contracts\Auth\Factory;
use Illuminate\Database\UniqueConstraintViolationException;
use TresPontosTech\Api\Enums\PushPlatform;
use TresPontosTech\Api\Enums\TokenAbility;
use TresPontosTech\Api\Models\PushToken;

use function Pest\Laravel\deleteJson;
use function Pest\Laravel\postJson;
use function Pest\Laravel\putJson;

/**
 * Cabeçalho de uma sessão do app com token de acesso de verdade: o token de push se prende a ele.
 *
 * @return array{Authorization: string}
 */
function pushTokenSession(User $user, string $device = 'Android'): array
{
    return ['Authorization' => 'Bearer ' . $user->createToken($device, [TokenAbility::Employee->value])->plainTextToken];
}

beforeEach(function (): void {
    $this->employee = standaloneSubscriber();
});

it('ties the push token to the session that sent it', function (): void {
    putJson(route('api.v1.me.push-token.update'), [
        'token' => 'ExponentPushToken[abc123]',
        'platform' => 'android',
    ], pushTokenSession($this->employee))->assertNoContent();

    $pushToken = PushToken::query()->sole();

    expect($pushToken->user_id)->toBe($this->employee->getKey())
        ->and($pushToken->platform)->toBe(PushPlatform::Android)
        ->and($pushToken->personal_access_token_id)->toBe($this->employee->tokens()->sole()->getKey());
});

it('accepts both Expo prefixes and the same token twice', function (): void {
    $session = pushTokenSession($this->employee);

    putJson(route('api.v1.me.push-token.update'), ['token' => 'ExpoPushToken[xyz]', 'platform' => 'ios'], $session)
        ->assertNoContent();
    putJson(route('api.v1.me.push-token.update'), ['token' => 'ExpoPushToken[xyz]', 'platform' => 'ios'], $session)
        ->assertNoContent();

    expect(PushToken::query()->count())->toBe(1);
});

it('keeps one token per session, dropping the one the app replaced', function (): void {
    $session = pushTokenSession($this->employee);

    putJson(route('api.v1.me.push-token.update'), ['token' => 'ExponentPushToken[old]', 'platform' => 'android'], $session);
    putJson(route('api.v1.me.push-token.update'), ['token' => 'ExponentPushToken[new]', 'platform' => 'android'], $session);

    expect(PushToken::query()->pluck('token')->all())->toBe(['ExponentPushToken[new]']);
});

it('never stores two push tokens for one session', function (): void {
    $pushToken = PushToken::factory()->create(['user_id' => $this->employee->getKey()]);

    expect(fn () => PushToken::factory()->create([
        'user_id' => $this->employee->getKey(),
        'personal_access_token_id' => $pushToken->personal_access_token_id,
    ]))->toThrow(UniqueConstraintViolationException::class);
});

it('moves a token to whoever signs in on that phone', function (): void {
    $previousOwner = standaloneSubscriber();

    putJson(route('api.v1.me.push-token.update'), ['token' => 'ExponentPushToken[phone]', 'platform' => 'android'], pushTokenSession($previousOwner));

    resolve(Factory::class)->forgetGuards();

    putJson(route('api.v1.me.push-token.update'), ['token' => 'ExponentPushToken[phone]', 'platform' => 'android'], pushTokenSession($this->employee))
        ->assertNoContent();

    expect(PushToken::query()->sole()->user_id)->toBe($this->employee->getKey());
});

it('refuses what is not an Expo push token', function (array $payload, string $field): void {
    putJson(route('api.v1.me.push-token.update'), $payload, pushTokenSession($this->employee))
        ->assertUnprocessable()
        ->assertJsonValidationErrors($field);
})->with([
    'no token' => [['platform' => 'android'], 'token'],
    'a raw FCM token' => [['token' => 'fcm-device-token', 'platform' => 'android'], 'token'],
    'an unknown platform' => [['token' => 'ExponentPushToken[abc]', 'platform' => 'web'], 'platform'],
]);

it('forgets only the token of this phone', function (): void {
    $phone = pushTokenSession($this->employee, 'Android');
    $tablet = pushTokenSession($this->employee, 'iPhone');

    putJson(route('api.v1.me.push-token.update'), ['token' => 'ExponentPushToken[phone]', 'platform' => 'android'], $phone);
    resolve(Factory::class)->forgetGuards();
    putJson(route('api.v1.me.push-token.update'), ['token' => 'ExponentPushToken[tablet]', 'platform' => 'ios'], $tablet);
    resolve(Factory::class)->forgetGuards();

    deleteJson(route('api.v1.me.push-token.destroy'), headers: $phone)->assertNoContent();
    resolve(Factory::class)->forgetGuards();
    deleteJson(route('api.v1.me.push-token.destroy'), headers: $phone)->assertNoContent();

    expect(PushToken::query()->pluck('token')->all())->toBe(['ExponentPushToken[tablet]']);
});

it('drops the push token when the session ends', function (): void {
    $session = pushTokenSession($this->employee);

    putJson(route('api.v1.me.push-token.update'), ['token' => 'ExponentPushToken[abc]', 'platform' => 'android'], $session);
    resolve(Factory::class)->forgetGuards();

    postJson(route('api.v1.auth.logout'), headers: $session)->assertNoContent();

    expect(PushToken::query()->count())->toBe(0);
});

it('requires a session', function (): void {
    putJson(route('api.v1.me.push-token.update'), ['token' => 'ExponentPushToken[abc]', 'platform' => 'android'])
        ->assertUnauthorized();
});
