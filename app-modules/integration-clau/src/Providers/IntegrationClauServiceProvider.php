<?php

declare(strict_types=1);

namespace TresPontosTech\IntegrationClau\Providers;

use Illuminate\Support\ServiceProvider;

class IntegrationClauServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../../config/clau.php', 'clau');
    }

    public function boot(): void {}
}
