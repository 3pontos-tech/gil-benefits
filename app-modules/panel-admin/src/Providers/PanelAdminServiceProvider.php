<?php

namespace TresPontosTech\Admin\Providers;

use App\Filament\FilamentPanel;
use Filament\Panel;
use Illuminate\Support\ServiceProvider;

class PanelAdminServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        Panel::configureUsing(
            fn (Panel $panel) => match ($panel->currentPanel()) {
                FilamentPanel::Admin => $panel
                    ->discoverResources(in: modules_path('panel-admin/src/Filament/Resources'), for: 'TresPontosTech\\Admin\\Filament\\Resources')
                    ->discoverPages(in: modules_path('panel-admin/src/Filament/Pages'), for: 'TresPontosTech\\Admin\\Filament\\Pages')
                    ->discoverWidgets(in: modules_path('panel-admin/src/Filament/Widgets'), for: 'TresPontosTech\\Admin\\Filament\\Widgets')
                    ->discoverClusters(in: modules_path('panel-admin/src/Filament/Clusters'), for: 'TresPontosTech\\Admin\\Filament\\Clusters'),
                default => null,
            }
        );
    }

    public function boot(): void {}
}
