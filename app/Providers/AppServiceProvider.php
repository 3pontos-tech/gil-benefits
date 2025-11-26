<?php

namespace App\Providers;

use App\Filament\FilamentPanel;
use Filament\Panel;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->configureRateLimiting();
    }

    /**
     * Configure rate limiting for partner registration
     */
    protected function configureRateLimiting(): void
    {
        // Rate limit for viewing the registration page (more lenient)
        RateLimiter::for('partner-registration', function (Request $request) {
            return Limit::perMinute(30)->by($request->ip());
        });

        // Rate limit for form submissions (more restrictive)
        RateLimiter::for('partner-registration-submit', function (Request $request) {
            $limits = [
                // 5 attempts per minute per IP
                Limit::perMinute(5)->by($request->ip()),
                // 20 attempts per hour per IP (prevents sustained attacks)
                Limit::perHour(20)->by($request->ip()),
            ];
            
            // Additional limit by email if provided (prevents email enumeration)
            if ($request->filled('email')) {
                $limits[] = Limit::perHour(3)->by($request->input('email'));
            }
            
            return $limits;
        });
    }

    public function register(): void
    {
        Relation::morphMap([
            'user' => config('auth.providers.users.model'),
        ]);

        Panel::macro('discoverResourcesForPanel', function (string $module, FilamentPanel $panel): void {
            $studlyPanel = str($panel->value)->studly();

            $filamentModulePath = module_path($module, sprintf('src/Filament/%s', $studlyPanel));
            $filamentModuleNamespace = sprintf('TresPontosTech\\%s\\Filament\\%s', str($module)->studly(), $studlyPanel);

            $in = $filamentModulePath . '/Resources';
            $for = $filamentModuleNamespace . '\\Resources';

            $this
                ->discoverResources(
                    in: $in,
                    for: $for,
                )
                ->discoverWidgets(
                    in: $filamentModulePath . '/Widgets',
                    for: $filamentModuleNamespace . '\\Widgets',
                )
                ->discoverPages(
                    in: $filamentModulePath . '/Pages',
                    for: $filamentModuleNamespace . '\\Pages',
                );
        });
    }
}
