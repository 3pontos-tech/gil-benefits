<?php

declare(strict_types=1);

namespace TresPontosTech\PanelApp\Providers;

use Illuminate\Support\ServiceProvider;

class PanelAppServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadTranslationsFrom(__DIR__ . '/../../lang', 'panel-app');
        $this->loadViewsFrom(__DIR__ . '/../../resources/views', 'panel-app');
    }
}
