<?php

declare(strict_types=1);

use App\Models\Users\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Testing\TestResponse;
use TresPontosTech\Api\Mail\PasswordResetCodeMail;
use TresPontosTech\Api\Models\PasswordResetCode;
use TresPontosTech\Api\Support\ApiDates;
use TresPontosTech\Api\Support\PasswordResetSecret;

use function Pest\Laravel\postJson;

beforeEach(function (): void {
    config()->set('api.password_reset.timebox_ms', 0);
});

function verifyPasswordCode(string $email, string $code): TestResponse
{
    return postJson(route('api.v1.auth.password.verify'), ['email' => $email, 'code' => $code]);
}

function userWithPasswordCode(string $code = '123456'): User
{
    $user = standaloneSubscriber();
    PasswordResetCode::factory()->withCode($code)->for($user)->create();

    return $user;
}

const PASSWORD_CODE_INVALID = ['code' => 'Código inválido ou expirado. Confira ou peça um novo.'];

it('exchanges the right code for a single-use reset token', function (): void {
    $this->freezeSecond();
    $user = userWithPasswordCode();

    $response = verifyPasswordCode($user->email, '123456')->assertOk();

    $token = $response->json('data.reset_token');
    $row = PasswordResetCode::query()->where('user_id', $user->id)->sole();

    expect($token)->toBeString()->toHaveLength(64)
        ->and(PasswordResetSecret::matches($row->reset_token_hash, $token))->toBeTrue()
        ->and($row->code_hash)->toBeNull()
        ->and($response->json('data.expires_at'))->toBe(ApiDates::dateTime(now()->addMinutes(15)));

    verifyPasswordCode($user->email, '123456')
        ->assertUnprocessable()
        ->assertJsonValidationErrors(PASSWORD_CODE_INVALID);
});

it('accepts the code as pasted from the email', function (string $typed): void {
    $user = userWithPasswordCode('123456');

    verifyPasswordCode($user->email, $typed)->assertOk();
})->with(['123 456', '123-456', ' 123456 ']);

it('gives the same error for every failure', function (string $case): void {
    $user = userWithPasswordCode('123456');
    userWithPasswordCode('654321');

    if ($case === 'expired code') {
        $this->travel(16)->minutes();
    }

    [$email, $code] = match ($case) {
        'wrong code' => [$user->email, '000000'],
        'expired code' => [$user->email, '123456'],
        "another account's code" => [$user->email, '654321'],
        'unknown email' => ['ninguem@empresa.com', '123456'],
    };

    verifyPasswordCode($email, $code)
        ->assertUnprocessable()
        ->assertExactJson([
            'message' => PASSWORD_CODE_INVALID['code'],
            'errors' => ['code' => [PASSWORD_CODE_INVALID['code']]],
        ]);
})->with(['wrong code', 'expired code', "another account's code", 'unknown email']);

it('drops the code on the fifth wrong attempt', function (): void {
    $user = userWithPasswordCode('123456');

    foreach (range(1, 5) as $attempt) {
        verifyPasswordCode($user->email, '000000')->assertUnprocessable();
    }

    verifyPasswordCode($user->email, '123456')
        ->assertUnprocessable()
        ->assertJsonValidationErrors(PASSWORD_CODE_INVALID);

    $row = PasswordResetCode::query()->where('user_id', $user->id)->sole();

    expect($row->code_hash)->toBeNull()
        ->and($row->failed_attempts)->toBe(5);
});

it('keeps the account errors when a new code replaces the previous one', function (): void {
    $this->freezeSecond();
    Mail::fake();
    $user = standaloneSubscriber();

    postJson(route('api.v1.auth.password.forgot'), ['email' => $user->email])->assertAccepted();
    $first = Mail::queued(PasswordResetCodeMail::class)->last()->code;
    $wrong = $first === '000000' ? '111111' : '000000';

    foreach (range(1, 4) as $attempt) {
        verifyPasswordCode($user->email, $wrong)->assertUnprocessable();
    }

    $this->travel(61)->seconds();
    postJson(route('api.v1.auth.password.forgot'), ['email' => $user->email])->assertAccepted();
    $second = Mail::queued(PasswordResetCodeMail::class)->last()->code;

    expect(PasswordResetCode::query()->sole()->failed_attempts)->toBe(4);

    if ($first !== $second) {
        verifyPasswordCode($user->email, $first)->assertUnprocessable();
    }

    verifyPasswordCode($user->email, $second)->assertOk();
});

it('refuses even a right code once the account reaches 20 errors', function (): void {
    $user = standaloneSubscriber();
    $row = PasswordResetCode::factory()->withCode('111111')->for($user)->create(['failed_attempts' => 19]);

    verifyPasswordCode($user->email, '000000')->assertUnprocessable();

    expect($row->fresh()->failed_attempts)->toBe(20);

    $row->update([
        'code_hash' => PasswordResetSecret::hash('222222'),
        'code_attempts' => 0,
        'code_expires_at' => now()->addMinutes(15),
    ]);

    verifyPasswordCode($user->email, '222222')
        ->assertUnprocessable()
        ->assertJsonValidationErrors(PASSWORD_CODE_INVALID);
});

it('throttles the eleventh check per IP in a minute', function (): void {
    foreach (range(1, 10) as $attempt) {
        verifyPasswordCode('ninguem@empresa.com', '000000')->assertUnprocessable();
    }

    verifyPasswordCode('ninguem@empresa.com', '000000')
        ->assertTooManyRequests()
        ->assertExactJson(['message' => 'Muitas tentativas. Aguarde um instante e tente de novo.']);
});

it('waits the minimum time on a failure and answers a right code at once', function (): void {
    config()->set('api.password_reset.timebox_ms', 400);
    $log = recordTimebox();
    $user = userWithPasswordCode('123456');

    verifyPasswordCode('ninguem@empresa.com', '123456')->assertUnprocessable();
    verifyPasswordCode($user->email, '123456')->assertOk();

    expect($log->getArrayCopy())->toHaveCount(2)
        ->and($log[0])->toBeInt()->toBeGreaterThan(0)->toBeLessThanOrEqual(400_000)
        ->and($log[1])->toBe('early');
});

it('validates the payload in Portuguese', function (): void {
    postJson(route('api.v1.auth.password.verify'), ['email' => 'not-an-email'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['email', 'code'])
        ->assertJsonPath('errors.code.0', 'O campo código é obrigatório.');
});
