<?php

namespace TresPontosTech\Appointments\Providers;

use Filament\Panel;
use Illuminate\Support\ServiceProvider;

class AppointmentsServiceProvider extends ServiceProvider
{
	public function register(): void
	{
        Panel::configureUsing(function (Panel $panel): void {
            if ($panel->getId() === 'admin') {
                $panel
                    ->discoverResources(
                        in: __DIR__ . '/../Filament/Admin/Resources',
                        for: 'TresPontosTech\\Appointments\\Filament\\Admin\\Resources'
                    )
                    ->discoverPages(
                        in: __DIR__ . '/../Filament/Admin/Pages',
                        for: 'TresPontosTech\\Appointments\\Filament\\Admin\\Pages'
                    );
            }

            if ($panel->getId() === 'app') {
                $panel
                    ->discoverResources(
                        in: __DIR__ . '/../Filament/App/Resources',
                        for: 'TresPontosTech\\Appointments\\Filament\\App\\Resources'
                    )
                    ->discoverPages(
                        in: __DIR__ . '/../Filament/App/Pages',
                        for: 'TresPontosTech\\Appointments\\Filament\\App\\Pages'
                    );
            }
        });

    }

	public function boot(): void
	{
	}
}
