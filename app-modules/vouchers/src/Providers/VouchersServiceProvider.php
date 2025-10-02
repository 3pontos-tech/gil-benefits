<?php

namespace TresPontosTech\Vouchers\Providers;

use Filament\Panel;
use Illuminate\Support\ServiceProvider;

class VouchersServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        Panel::configureUsing(function (Panel $panel): void {
            if ($panel->getId() === 'admin') {
                $panel
                    ->discoverResources(
                        in: __DIR__ . '/../Filament/Admin/Resources',
                        for: 'TresPontosTech\\Vouchers\\Filament\\Admin\\Resources'
                    )
                    ->discoverWidgets(
                        in: __DIR__ . '/../Filament/Admin/Widgets',
                        for: 'TresPontosTech\\Vouchers\\Filament\\Admin\\Widgets'
                    )
                    ->discoverPages(
                        in: __DIR__ . '/../Filament/Admin/Pages',
                        for: 'TresPontosTech\\Vouchers\\Filament\\Admin\\Pages'
                    );
            }

            if ($panel->getId() === 'company') {
                $panel
                    ->discoverResources(
                        in: __DIR__ . '/../Filament/Company/Resources',
                        for: 'TresPontosTech\\Vouchers\\Filament\\Company\\Resources'
                    )
                    ->discoverWidgets(
                        in: __DIR__ . '/../Filament/Company/Widgets',
                        for: 'TresPontosTech\\Vouchers\\Filament\\Company\\Widgets'
                    )
                    ->discoverPages(
                        in: __DIR__ . '/../Filament/Company/Pages',
                        for: 'TresPontosTech\\Vouchers\\Filament\\Company\\Pages'
                    );
            }
        });
    }

    public function boot(): void {}
}
