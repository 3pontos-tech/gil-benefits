<?php

namespace App\Providers;

use App\Filament\FilamentPanel;
use Filament\Panel;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\ServiceProvider;
use Laravel\Cashier\Cashier;
use TresPontosTech\Billing\Core\Models\Subscription;
use TresPontosTech\Company\Models\Company;

class AppServiceProvider extends ServiceProvider
{
    public function boot(): void {}

    public function register(): void
    {
        Relation::morphMap([
            'user' => config('auth.providers.users.model'),
        ]);

        Cashier::useSubscriptionModel(Subscription::class);

        Panel::macro('discoverResourcesForPanel', function (string $module, FilamentPanel $panel): void {
            $studlyPanel = str($panel->value)->studly();

            $filamentModulePath = module_path($module, sprintf('src/Filament/%s', $studlyPanel));
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
