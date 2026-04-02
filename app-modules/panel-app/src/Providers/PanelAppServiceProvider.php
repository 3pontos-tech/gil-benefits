<?php

namespace TresPontosTech\App\Providers;

use Illuminate\Support\ServiceProvider;

class PanelAppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        $this->loadTranslationsFrom(__DIR__ . '/../../lang', 'panel-app');
        $this->loadViewsFrom(__DIR__ . '/../../resources/views', 'panel-app');
    }
}
