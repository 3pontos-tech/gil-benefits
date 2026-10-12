<?php

declare(strict_types=1);

use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Timebox;
use Illuminate\Testing\TestResponse;

use function Pest\Laravel\postJson;
use function Pest\Laravel\withServerVariables;

/**
 * O login da API não pode ser a porta mais fraca para as mesmas credenciais do painel (1.3).
 */
beforeEach(function (): void {
    RateLimiter::clear('api-login');
});

/**
 * @return ArrayObject<int, int|string> o que cada chamada ao Timebox fez: microssegundos de espera, ou "early"
 */
function recordTimebox(): ArrayObject
{
    $log = new ArrayObject;

    app()->bind(Timebox::class, fn (): Timebox => new class($log) extends Timebox
    {
        public function __construct(private readonly ArrayObject $log) {}

        public function call(callable $callback, int $microseconds): mixed
        {
            try {
                return parent::call($callback, $microseconds);
            } finally {
                if ($this->earlyReturn) {
                    $this->log->append('early');
                }
            }
        }

        protected function usleep(int $microseconds): void
        {
            $this->log->append($microseconds);
        }
    });

    return $log;
}

function attemptLogin(string $email, string $password = 'wrong'): TestResponse
{
    return postJson(route('api.v1.auth.login'), ['email' => $email, 'password' => $password, 'device_name' => 'Android']);
}

it('caps the attempts per IP across different emails (password spraying)', function (): void {
    config()->set('auth.timebox_duration', 0);

    foreach (range(1, 20) as $attempt) {
        attemptLogin(sprintf('pessoa%s@empresa.com', $attempt))->assertUnprocessable();
    }

    attemptLogin('pessoa21@empresa.com')->assertTooManyRequests();

    withServerVariables(['REMOTE_ADDR' => '10.0.0.2']);
    attemptLogin('pessoa22@empresa.com')->assertUnprocessable();
});

it('still lets other accounts behind the same IP in after one account is locked', function (): void {
    config()->set('auth.timebox_duration', 0);
    $locked = standaloneSubscriber();
    $colleague = standaloneSubscriber();

    foreach (range(1, 5) as $attempt) {
        attemptLogin($locked->email)->assertUnprocessable();
    }

    attemptLogin($locked->email)->assertTooManyRequests();
    attemptLogin($colleague->email, 'password')->assertOk();
});

it('takes the same minimum time for an unknown email and a wrong password', function (string $case): void {
    config()->set('auth.timebox_duration', 200_000);
    $log = recordTimebox();
    $user = standaloneSubscriber();

    attemptLogin($case === 'unknown email' ? 'ninguem@empresa.com' : $user->email)
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['email' => 'E-mail ou senha incorretos.']);

    expect($log->getArrayCopy())->toHaveCount(1)
        ->and($log[0])->toBeInt()->toBeGreaterThan(0)->toBeLessThanOrEqual(200_000);
})->with(['unknown email', 'wrong password']);

it('answers a correct login without waiting', function (): void {
    config()->set('auth.timebox_duration', 200_000);
    $log = recordTimebox();
    $user = standaloneSubscriber();

    attemptLogin($user->email, 'password')->assertOk();

    expect($log->getArrayCopy())->toBe(['early']);
});
