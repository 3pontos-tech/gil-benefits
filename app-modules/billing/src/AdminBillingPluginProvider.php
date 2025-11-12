<?php

namespace TresPontosTech\Billing;

use Filament\Contracts\Plugin;
use Filament\Panel;
use TresPontosTech\Billing\Core\Filament\Admin\Resources\Plans\PlanResource;
use TresPontosTech\Billing\Core\Filament\Admin\Resources\Prices\PriceResource;

class AdminBillingPluginProvider implements Plugin
{
    public function getId(): string
    {
        return 'billing-admin';
    }

    public function register(Panel $panel): void
    {
        $panel->resources([
            PlanResource::class,
            PriceResource::class,
        ]);
    }

    public function boot(Panel $panel): void {}
}
