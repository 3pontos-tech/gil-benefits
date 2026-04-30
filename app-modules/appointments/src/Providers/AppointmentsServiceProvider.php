<?php

declare(strict_types=1);

namespace TresPontosTech\Appointments\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use TresPontosTech\Appointments\Support\AiCircuitBreaker;

class AppointmentsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../../config/appointments.php', 'appointments');

        $this->app->bind(AiCircuitBreaker::class, fn (): AiCircuitBreaker => new AiCircuitBreaker(
            cooldownMinutes: (int) config('appointments.ai.circuit_cooldown_minutes', 3),
        ));
    }

    public function boot(): void
    {
        $this->loadTranslationsFrom(__DIR__ . '/../../lang', 'appointments');
        $this->loadViewsFrom(__DIR__ . '/../../resources/views', 'appointments');

        RateLimiter::for('appointment-record-ai', function () {
            return Limit::perMinute((int) config('appointments.ai.rate_limit_per_minute', 10));
        });

    }
}
