<?php

declare(strict_types=1);

namespace TresPontosTech\IntegrationGoogleCalendar\Providers;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use TresPontosTech\Appointments\Contracts\AppointmentCalendar;
use TresPontosTech\Appointments\Events\AppointmentCancelled;
use TresPontosTech\IntegrationGoogleCalendar\AppointmentCalendarSynchronizer;
use TresPontosTech\IntegrationGoogleCalendar\Console\Commands\SyncGoogleCalendarsCommand;
use TresPontosTech\IntegrationGoogleCalendar\Listeners\RemoveCalendarEventOnAppointmentCancelled;

class IntegrationGoogleCalendarServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../../config/google-calendar.php', 'google-calendar');

        $this->app->bind(AppointmentCalendar::class, AppointmentCalendarSynchronizer::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');

        Event::listen(AppointmentCancelled::class, RemoveCalendarEventOnAppointmentCancelled::class);

        $this->commands([
            SyncGoogleCalendarsCommand::class,
        ]);

        $this->app->booted(function (): void {
            $schedule = $this->app->make(Schedule::class);
            $schedule->command('google-calendar:sync')
                ->cron('*/20 * * * *')
                ->environments(['production']);
        });
    }
}
