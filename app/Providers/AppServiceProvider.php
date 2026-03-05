<?php

namespace App\Providers;

use App\Filament\FilamentPanel;
use Filament\Panel;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function boot(): void {}

    public function register(): void
    {
        Relation::morphMap([
            'user' => config('auth.providers.users.model'),
        ]);

        Panel::macro('discoverResourcesForPanel', function (string $module, FilamentPanel $panel): void {
            $studlyPanel = str($panel->value)->studly();

            $filamentModulePath = modules_path($module, sprintf('src/Filament/%s', $studlyPanel));
            $filamentModuleNamespace = sprintf('TresPontosTech\\%s\\Filament\\%s', str($module)->studly(), $studlyPanel);

            $in = $filamentModulePath . '/Resources';
            $for = $filamentModuleNamespace . '\\Resources';

            $this
                ->discoverResources(
                    in: $in,
                    for: $for,
                )
                ->discoverWidgets(
                    in: $filamentModulePath . '/Widgets',
                    for: $filamentModuleNamespace . '\\Widgets',
                )
                ->discoverPages(
                    in: $filamentModulePath . '/Pages',
                    for: $filamentModuleNamespace . '\\Pages',
                );
        });
    }
}
