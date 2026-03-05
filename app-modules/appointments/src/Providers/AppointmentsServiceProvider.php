<?php

namespace TresPontosTech\Appointments\Providers;

use Illuminate\Support\ServiceProvider;

class AppointmentsServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        // Load package translations for the appointments module
        $this->loadTranslationsFrom(__DIR__ . '/../../lang', 'appointments');

    }
}
