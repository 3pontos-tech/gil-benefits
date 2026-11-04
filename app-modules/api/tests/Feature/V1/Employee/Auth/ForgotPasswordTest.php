<?php

declare(strict_types=1);

use App\Models\Users\User;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use TresPontosTech\Api\Mail\PasswordResetCodeMail;
use TresPontosTech\Api\Models\PasswordResetCode;
use TresPontosTech\Api\Support\PasswordResetSecret;
use TresPontosTech\Company\Models\Company;
use TresPontosTech\Permissions\Roles;

use function Pest\Laravel\postJson;
use function Pest\Laravel\withServerVariables;

/**
 * Pedir o código nunca pode dizer quem tem conta (#323): mesma resposta, mesmo tempo e mesmos
 * limites para todo e-mail.
 */
beforeEach(function (): void {
    config()->set('api.password_reset.timebox_ms', 0);
    Mail::fake();
});

function requestPasswordCode(string $email): TestResponse
{
    return postJson(route('api.v1.auth.password.forgot'), ['email' => $email]);
}

function queuedPasswordCode(User $user): string
{
    $mail = Mail::queued(PasswordResetCodeMail::class, fn (PasswordResetCodeMail $mail): bool => $mail->hasTo($user->email))->last();

    expect($mail)->toBeInstanceOf(PasswordResetCodeMail::class);

    return $mail->code;
}

const PASSWORD_CODE_SENT = ['message' => 'Se esse e-mail tiver acesso ao app, enviamos um código.'];

it('mails a 6-digit code to an account with app access and keeps only its hash', function (): void {
    $this->freezeSecond();
    $user = standaloneSubscriber();

    requestPasswordCode($user->email)
        ->assertAccepted()
        ->assertExactJson(PASSWORD_CODE_SENT);

    $code = queuedPasswordCode($user);
    $row = PasswordResetCode::query()->where('user_id', $user->id)->sole();

    expect($code)->toMatch('/^\d{6}$/')
        ->and($row->code_hash)->not->toBe($code)
        ->and(PasswordResetSecret::matches($row->code_hash, $code))->toBeTrue()
        ->and($row->code_attempts)->toBe(0)
        ->and($row->code_expires_at->equalTo(now()->addMinutes(15)))->toBeTrue();
});

it('lets a B2B employee ask for a code', function (): void {
    $employee = actingAsEmployee();
    auth()->logout();

    requestPasswordCode($employee->email)->assertAccepted();

    expect(queuedPasswordCode($employee))->toMatch('/^\d{6}$/');
});

it('mails only the code, without a reset link, and keeps it encrypted on the queue', function (): void {
    config()->set('app.name', 'Flamma');
    $mail = new PasswordResetCodeMail('042137');

    $html = $mail->render();
    preg_match_all('/href="([^"]*)"/', $html, $links);

    $mail->assertHasSubject('Seu código para redefinir a senha do Flamma');

    expect($mail)->toBeInstanceOf(ShouldBeEncrypted::class)
        ->and($mail->queue)->toBe('emails')
        ->and($html)->toContain('042137')
        ->toContain('Digite este código no app Flamma. Ele vale por 15 minutos.')
        ->toContain('Se não foi você, ignore este e-mail: a sua senha continua a mesma.')
        ->and(array_unique($links[1]))->each->toBe(config('app.url'));
});

it('answers the same and sends nothing to an account that cannot use the app', function (string $email): void {
    requestPasswordCode($email)
        ->assertAccepted()
        ->assertExactJson(PASSWORD_CODE_SENT);

    Mail::assertNothingQueued();

    expect(PasswordResetCode::query()->count())->toBe(0);
})->with([
    'unknown email' => fn (): string => 'ninguem@empresa.com',
    'account without app access' => fn (): string => User::factory()->create()->email,
    'consultant, even with the benefit' => function (): string {
        $consultant = standaloneSubscriber();
        $consultant->assignRole(Roles::Consultant);

        return $consultant->email;
    },
    'admin without an employee link' => fn (): string => User::factory()->admin()->create()->email,
]);

it('mails the code to an employee who also has power in a panel', function (string $power): void {
    $user = standaloneSubscriber();

    match ($power) {
        'manages a company' => Company::factory()->create()->employees()->attach($user->getKey(), ['role' => Roles::CompanyManager->value]),
        'is an admin' => $user->assignRole(Roles::Admin),
    };

    requestPasswordCode($user->email)->assertAccepted();

    expect(queuedPasswordCode($user))->toMatch('/^\d{6}$/');
})->with(['manages a company', 'is an admin']);

it('sends nothing inside the resend wait and keeps the first code', function (): void {
    $this->freezeSecond();
    $user = standaloneSubscriber();

    requestPasswordCode($user->email)->assertAccepted();
    $first = queuedPasswordCode($user);

    $this->travel(59)->seconds();
    requestPasswordCode($user->email)->assertAccepted()->assertExactJson(PASSWORD_CODE_SENT);

    Mail::assertQueued(PasswordResetCodeMail::class, 1);
    expect(PasswordResetSecret::matches(PasswordResetCode::query()->sole()->code_hash, $first))->toBeTrue();

    $this->travel(1)->seconds();
    requestPasswordCode($user->email)->assertAccepted();

    Mail::assertQueued(PasswordResetCodeMail::class, 2);
});

it('sends nothing to an account locked by failed attempts', function (): void {
    $user = standaloneSubscriber();
    PasswordResetCode::factory()->lockedOut()->for($user)->create();

    requestPasswordCode($user->email)
        ->assertAccepted()
        ->assertExactJson(PASSWORD_CODE_SENT);

    Mail::assertNothingQueued();
});

it('finds the account whatever the case of the typed email', function (): void {
    $user = standaloneSubscriber(['email' => 'maria@empresa.com']);

    requestPasswordCode('  ' . Str::upper($user->email) . '  ')->assertAccepted();

    expect(queuedPasswordCode($user))->toMatch('/^\d{6}$/');
});

it('throttles the fourth request in 15 minutes the same way for any email', function (): void {
    $user = standaloneSubscriber();

    foreach (range(1, 3) as $attempt) {
        requestPasswordCode($user->email)->assertAccepted();
        requestPasswordCode('ninguem@empresa.com')->assertAccepted();
    }

    $known = requestPasswordCode($user->email)->assertTooManyRequests();
    $unknown = requestPasswordCode('ninguem@empresa.com')->assertTooManyRequests();

    expect($known->json())->toBe($unknown->json())
        ->toBe(['message' => 'Muitas tentativas. Aguarde um instante e tente de novo.']);
});

it('caps the requests per IP across different emails', function (): void {
    foreach (range(1, 20) as $attempt) {
        requestPasswordCode(sprintf('pessoa%s@empresa.com', $attempt))->assertAccepted();
    }

    requestPasswordCode('pessoa21@empresa.com')->assertTooManyRequests();

    withServerVariables(['REMOTE_ADDR' => '10.0.0.2']);
    requestPasswordCode('pessoa22@empresa.com')->assertAccepted();
});

it('always waits the full minimum time, with or without an account', function (string $case): void {
    config()->set('api.password_reset.timebox_ms', 400);
    $log = recordTimebox();
    $user = standaloneSubscriber();

    requestPasswordCode($case === 'unknown email' ? 'ninguem@empresa.com' : $user->email)->assertAccepted();

    expect($log->getArrayCopy())->toHaveCount(1)
        ->and($log[0])->toBeInt()->toBeGreaterThan(0)->toBeLessThanOrEqual(400_000);
})->with(['unknown email', 'account with access']);

it('validates the email in Portuguese', function (): void {
    postJson(route('api.v1.auth.password.forgot'), ['email' => 'not-an-email'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['email' => 'O campo e-mail deve ser um endereço de e-mail válido.']);
});
