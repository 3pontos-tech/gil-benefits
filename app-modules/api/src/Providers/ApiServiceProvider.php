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
     * Limites por público (A10): o colaborador autenticado conta por usuário e o
     * anônimo por IP; o login conta por e-mail + IP para não travar um escritório
     * inteiro atrás do mesmo NAT por causa de uma única conta.
     */
    private function registerRateLimiters(): void
    {
        RateLimiter::for('api-employee', fn (Request $request): Limit => Limit::perMinute((int) config('api.rate_limits.employee_per_minute'))
            ->by($request->user()?->getAuthIdentifier() ?? $request->ip()));

        RateLimiter::for('api-login', fn (Request $request): Limit => Limit::perMinute((int) config('api.rate_limits.login_per_minute'))
            ->by(Str::lower($request->string('email')->toString()) . '|' . $request->ip()));
    }
}
