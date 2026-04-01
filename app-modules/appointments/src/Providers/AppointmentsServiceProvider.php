<?php

namespace TresPontosTech\Appointments\Providers;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\ServiceProvider;
use TresPontosTech\Appointments\Jobs\MarkAppointmentsAsCompleted;

class AppointmentsServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        $this->loadTranslationsFrom(__DIR__ . '/../../lang', 'appointments');

        $this->app->booted(function (): void {
            $schedule = $this->app->make(Schedule::class);
            $schedule->job(new MarkAppointmentsAsCompleted)->hourly()->withoutOverlapping();
        });
    }
}
