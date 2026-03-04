<?php

namespace TresPontosTech\IntegrationGoogleCalendar\Providers;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\ServiceProvider;
use TresPontosTech\IntegrationGoogleCalendar\Console\Commands\SyncGoogleCalendarsCommand;

class IntegrationGoogleCalendarServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../../config/google-calendar.php', 'google-calendar');
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');

        $this->commands([
            SyncGoogleCalendarsCommand::class,
        ]);

        $this->app->booted(function (): void {
            $schedule = $this->app->make(Schedule::class);
            $schedule->command('google-calendar:sync')->everyThirtyMinutes();
        });
    }
}
