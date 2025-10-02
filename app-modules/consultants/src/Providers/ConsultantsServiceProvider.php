<?php

namespace TresPontosTech\Consultants\Providers;

use Filament\Panel;
use Illuminate\Support\ServiceProvider;

class ConsultantsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        Panel::configureUsing(function (Panel $panel): void {
            if ($panel->getId() === 'admin') {
                $panel
                    ->discoverResources(
                        in: __DIR__ . '/../Filament/Admin/Resources',
                        for: 'TresPontosTech\\Consultants\\Filament\\Admin\\Resources'
                    )
                    ->discoverPages(
                        in: __DIR__ . '/../Filament/Admin/Pages',
                        for: 'TresPontosTech\\Consultants\\Filament\\Admin\\Pages'
                    );
            }
        });
    }

    public function boot(): void {}
}
