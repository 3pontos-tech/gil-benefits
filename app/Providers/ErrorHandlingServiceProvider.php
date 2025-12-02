<?php

declare(strict_types=1);

namespace App\Providers;

use App\Services\Database\TransactionManager;
use App\Services\Logging\ActivityLogger;
use App\Services\Logging\StructuredLogger;
use App\Services\Monitoring\AlertManager;
use App\Services\Monitoring\SystemMonitor;
use Illuminate\Support\ServiceProvider;

class ErrorHandlingServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Register structured logger
        $this->app->singleton(StructuredLogger::class, function ($app) {
            return new StructuredLogger(config('logging.default'));
        });

        // Register activity logger
        $this->app->singleton(ActivityLogger::class, function ($app) {
            return new ActivityLogger($app->make(StructuredLogger::class));
        });

        // Register transaction manager
        $this->app->singleton(TransactionManager::class, function ($app) {
            return new TransactionManager($app->make(StructuredLogger::class));
        });

        // Register alert manager
        $this->app->singleton(AlertManager::class, function ($app) {
            return new AlertManager($app->make(StructuredLogger::class));
        });

        // Register system monitor
        $this->app->singleton(SystemMonitor::class, function ($app) {
            return new SystemMonitor(
                $app->make(StructuredLogger::class),
                $app->make(AlertManager::class)
            );
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
