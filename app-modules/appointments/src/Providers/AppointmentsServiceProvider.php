<?php

namespace TresPontosTech\Appointments\Providers;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\ServiceProvider;
use TresPontosTech\Appointments\Jobs\MarkAppointmentsAsCompleted;
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

        $this->app->booted(function (): void {
            $schedule = $this->app->make(Schedule::class);
            $schedule->job(new MarkAppointmentsAsCompleted)->hourly()->withoutOverlapping();
        });
    }
}
