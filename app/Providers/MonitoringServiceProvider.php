<?php

declare(strict_types=1);

namespace App\Providers;

use App\Services\Monitoring\DatabaseQueryAnalyzer;
use Illuminate\Support\ServiceProvider;

class MonitoringServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Register monitoring services as singletons
        $this->app->singleton(\App\Services\Monitoring\SystemMonitor::class);
        $this->app->singleton(\App\Services\Monitoring\AlertManager::class);
        $this->app->singleton(\App\Services\Monitoring\PerformanceMetricsCollector::class);
        $this->app->singleton(\App\Services\Monitoring\DatabaseQueryAnalyzer::class);
        $this->app->singleton(\App\Services\Monitoring\UserActivityTracker::class);
        $this->app->singleton(\App\Services\Monitoring\MonitoringDashboard::class);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Start database query monitoring if enabled
        if (config('monitoring.enabled', true)) {
            $queryAnalyzer = $this->app->make(DatabaseQueryAnalyzer::class);
            $queryAnalyzer->startMonitoring();
        }
    }
}
