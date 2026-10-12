<?php

declare(strict_types=1);

namespace TresPontosTech\Api\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class ApiServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../../config/api.php', 'api');
    }

    public function boot(): void
    {
        $this->loadTranslationsFrom(__DIR__ . '/../../lang', 'api');

        $this->registerRateLimiters();
    }

    /**
     * Limites por público (A10). O colaborador conta por usuário: a autenticação roda antes do
     * throttle, então toda requisição que chega aqui já tem dono.
     *
     * O login tem dois tetos. Por e-mail + IP, para uma conta não travar um escritório inteiro
     * atrás do mesmo NAT; e por IP, mais alto, para um IP não testar senhas em e-mails diferentes
     * sem limite (o painel limita por IP).
     */
    private function registerRateLimiters(): void
    {
        RateLimiter::for('api-employee', fn (Request $request): Limit => Limit::perMinute((int) config('api.rate_limits.employee_per_minute'))
            ->by($request->user()?->getAuthIdentifier() ?? $request->ip()));

        RateLimiter::for('api-login', function (Request $request): array {
            $email = $request->input('email');

            return [
                Limit::perMinute((int) config('api.rate_limits.login_per_minute'))
                    ->by('email:' . (is_string($email) ? Str::lower($email) : '') . '|' . $request->ip()),
                Limit::perMinute((int) config('api.rate_limits.login_per_minute_per_ip'))
                    ->by('ip:' . $request->ip()),
            ];
        });
    }
}
