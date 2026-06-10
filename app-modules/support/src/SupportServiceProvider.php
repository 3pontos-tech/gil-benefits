<?php

declare(strict_types=1);

namespace TresPontosTech\Support;

use Illuminate\Support\ServiceProvider;
use Override;
use TresPontosTech\Support\Services\ProtocolGenerator;
use TresPontosTech\Support\Services\TicketRouterService;

class SupportServiceProvider extends ServiceProvider
{
    #[Override]
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/support.php', 'support');

        $this->app->singleton(ProtocolGenerator::class);
        $this->app->singleton(TicketRouterService::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
        $this->loadTranslationsFrom(__DIR__ . '/../lang', 'support');
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'support');

        $this->publishes([
            __DIR__ . '/../config/support.php' => config_path('support.php'),
        ], 'support-config');
    }
}
