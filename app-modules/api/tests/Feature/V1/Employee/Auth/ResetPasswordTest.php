<?php

declare(strict_types=1);

use App\Models\Users\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use TresPontosTech\Api\Mail\PasswordResetCodeMail;
use TresPontosTech\Api\Models\PasswordResetCode;
use TresPontosTech\User\Mail\PasswordChangedMail;

use function Pest\Laravel\postJson;

beforeEach(function (): void {
    config()->set('api.password_reset.timebox_ms', 0);
    config()->set('auth.timebox_duration', 0);
    Mail::fake();
});

function resetPasswordWith(string $email, string $token, string $password = 'NovaSenha#2026', ?string $confirmation = null): TestResponse
{
    return postJson(route('api.v1.auth.password.reset'), [
        'email' => $email,
        'reset_token' => $token,
        'password' => $password,
        'password_confirmation' => $confirmation ?? $password,
    ]);
}

/**
 * @param  array<string, mixed>  $attributes
 */
function userWithResetToken(string $token, array $attributes = []): User
{
    $user = standaloneSubscriber($attributes);
    PasswordResetCode::factory()->withResetToken($token)->for($user)->create();

    return $user;
}

function webSessionFor(User $user, string $id): void
{
    DB::table('sessions')->insert([
        'id' => $id,
        'user_id' => $user->getKey(),
        'ip_address' => '127.0.0.1',
        'user_agent' => 'Pest',
        'payload' => '',
        'last_activity' => now()->getTimestamp(),
    ]);
}

const PASSWORD_RESET_EXPIRED = ['reset_token' => 'O tempo para criar a nova senha acabou. Peça um novo código.'];

it('recovers the account from the code to a sign-in with the new password', function (): void {
    $user = standaloneSubscriber();

    postJson(route('api.v1.auth.password.forgot'), ['email' => $user->email])->assertAccepted();
    $code = Mail::queued(PasswordResetCodeMail::class)->last()->code;

    $token = postJson(route('api.v1.auth.password.verify'), ['email' => $user->email, 'code' => $code])
        ->assertOk()
        ->json('data.reset_token');

    resetPasswordWith($user->email, $token, 'NovaSenha#2026')->assertNoContent();

    postJson(route('api.v1.auth.login'), ['email' => $user->email, 'password' => 'password', 'device_name' => 'Android'])
        ->assertUnprocessable();
    postJson(route('api.v1.auth.login'), ['email' => $user->email, 'password' => 'NovaSenha#2026', 'device_name' => 'Android'])
        ->assertOk();
});

it('sets the new password and signs every device and session out', function (): void {
    config()->set('session.driver', 'database');
    $token = Str::random(64);
    $user = userWithResetToken($token, ['last_login_at' => now()->subDay()]);
    $user->createToken('Android', ['employee']);
    $user->createToken('iPhone', ['employee']);
    webSessionFor($user, 'own-session');
    webSessionFor(standaloneSubscriber(), 'someone-else');
    $siteLink = Password::broker()->createToken($user);
    $rememberToken = $user->getRememberToken();

    resetPasswordWith($user->email, $token)->assertNoContent();

    $user->refresh();

    expect(Hash::check('NovaSenha#2026', $user->password))->toBeTrue()
        ->and($user->tokens()->count())->toBe(0)
        ->and($user->getRememberToken())->not->toBeNull()->not->toBe($rememberToken)
        ->and(DB::table('sessions')->pluck('id')->all())->toBe(['someone-else'])
        ->and(PasswordResetCode::query()->where('user_id', $user->id)->exists())->toBeFalse()
        ->and(Password::broker()->tokenExists($user, $siteLink))->toBeFalse();

    Mail::assertQueued(PasswordChangedMail::class, fn (PasswordChangedMail $mail): bool => $mail->hasTo($user->email));
});

it('dispatches PasswordReset, like the reset on the website', function (): void {
    Event::fake([PasswordReset::class]);
    $token = Str::random(64);
    $user = userWithResetToken($token);

    resetPasswordWith($user->email, $token)->assertNoContent();

    Event::assertDispatched(PasswordReset::class, fn (PasswordReset $event): bool => $event->user->is($user));
});

it('refuses a token that is expired, foreign or for an unknown email', function (string $case): void {
    $token = Str::random(64);
    $user = userWithResetToken($token);
    $otherToken = Str::random(64);
    userWithResetToken($otherToken);

    if ($case === 'expired') {
        $this->travel(16)->minutes();
    }

    [$email, $sent] = match ($case) {
        'expired' => [$user->email, $token],
        "another account's token" => [$user->email, $otherToken],
        'unknown email' => ['ninguem@empresa.com', $token],
    };

    resetPasswordWith($email, $sent)
        ->assertUnprocessable()
        ->assertJsonValidationErrors(PASSWORD_RESET_EXPIRED);

    expect(Hash::check('password', $user->fresh()->password))->toBeTrue();
})->with(['expired', "another account's token", 'unknown email']);

it('refuses a token that was already used', function (): void {
    $token = Str::random(64);
    $user = userWithResetToken($token);

    resetPasswordWith($user->email, $token, 'NovaSenha#2026')->assertNoContent();

    resetPasswordWith($user->email, $token, 'OutraSenha#2026')
        ->assertUnprocessable()
        ->assertJsonValidationErrors(PASSWORD_RESET_EXPIRED);

    expect(Hash::check('NovaSenha#2026', $user->fresh()->password))->toBeTrue();
});

it('rejects a weak password or a different confirmation and changes nothing', function (string $password, string $confirmation): void {
    $token = Str::random(64);
    $user = userWithResetToken($token);

    resetPasswordWith($user->email, $token, $password, $confirmation)
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['password']);

    expect(Hash::check('password', $user->fresh()->password))->toBeTrue()
        ->and(PasswordResetCode::query()->where('user_id', $user->id)->sole()->reset_token_hash)->not->toBeNull();
})->with([
    'too short' => ['curta', 'curta'],
    'different confirmation' => ['NovaSenha#2026', 'OutraSenha#2026'],
]);

it('waits the minimum time on a failure and answers a valid token at once', function (): void {
    config()->set('api.password_reset.timebox_ms', 400);
    $log = recordTimebox();
    $token = Str::random(64);
    $user = userWithResetToken($token);

    resetPasswordWith('ninguem@empresa.com', $token)->assertUnprocessable();
    resetPasswordWith($user->email, $token)->assertNoContent();

    expect($log->getArrayCopy())->toHaveCount(2)
        ->and($log[0])->toBeInt()->toBeGreaterThan(0)->toBeLessThanOrEqual(400_000)
        ->and($log[1])->toBe('early');
});
