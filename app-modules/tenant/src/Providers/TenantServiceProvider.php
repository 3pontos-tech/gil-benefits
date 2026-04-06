<?php

declare(strict_types=1);

namespace TresPontosTech\Tenant\Providers;

use Illuminate\Support\ServiceProvider;

class TenantServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../../config/tenant_integration.php', 'tenant');
    }

    public function boot(): void {}
}
