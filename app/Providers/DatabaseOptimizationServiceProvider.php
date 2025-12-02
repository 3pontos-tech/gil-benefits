<?php

namespace App\Providers;

use App\Models\Users\User;
use App\Observers\CacheInvalidationObserver;
use App\Services\CacheService;
use App\Services\QueryOptimizationService;
use Illuminate\Support\ServiceProvider;
use TresPontosTech\Appointments\Models\Appointment;
use TresPontosTech\Company\Models\Company;
use TresPontosTech\Consultants\Models\Consultant;

class DatabaseOptimizationServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(CacheService::class);
        $this->app->singleton(QueryOptimizationService::class);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Register cache invalidation observers
        $this->registerObservers();

        // Enable query optimization in non-production environments
        if (! $this->app->isProduction()) {
            $this->app->make(QueryOptimizationService::class);
        }
    }

    /**
     * Register model observers for cache invalidation.
     */
    private function registerObservers(): void
    {
        User::observe(CacheInvalidationObserver::class);
        Company::observe(CacheInvalidationObserver::class);
        Appointment::observe(CacheInvalidationObserver::class);
        Consultant::observe(CacheInvalidationObserver::class);
    }
}
