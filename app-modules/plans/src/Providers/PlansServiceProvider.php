<?php

namespace TresPontosTech\Plans\Providers;

use Filament\Panel;
use Illuminate\Support\ServiceProvider;

class PlansServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        Panel::configureUsing(function (Panel $panel): void {
            if ($panel->getId() === 'admin') {
                $panel
                    ->discoverResources(
                        in: __DIR__ . '/../Filament/Admin/Resources',
                        for: 'TresPontosTech\\Plans\\Filament\\Admin\\Resources'
                    )
                    ->discoverWidgets(
                        in: __DIR__ . '/../Filament/Admin/Widgets',
                        for: 'TresPontosTech\\Plans\\Filament\\Admin\\Widgets'
                    )
                    ->discoverPages(
                        in: __DIR__ . '/../Filament/Admin/Pages',
                        for: 'TresPontosTech\\Plans\\Filament\\Admin\\Pages'
                    );
            }
        });
    }

    public function boot(): void {}
}
