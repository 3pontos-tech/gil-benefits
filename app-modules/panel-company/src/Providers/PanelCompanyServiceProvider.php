<?php

declare(strict_types=1);

namespace TresPontosTech\PanelCompany\Providers;

use Illuminate\Support\ServiceProvider;

class PanelCompanyServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        $this->loadTranslationsFrom(__DIR__ . '/../../lang', 'panel-company');
    }
}
