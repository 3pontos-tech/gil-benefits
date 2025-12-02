<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View as ViewInstance;

class ViewCacheService
{
    private const VIEW_CACHE_TTL = 3600; // 1 hour
    private const COMPONENT_CACHE_TTL = 1800; // 30 minutes
    private const STATIC_CACHE_TTL = 86400; // 24 hours

    private const CACHE_PREFIX = 'view_cache:';

    /**
     * Cache a rendered view.
     */
    public function cacheView(string $viewName, array $data = [], ?int $ttl = null): string
    {
        $cacheKey = $this->generateViewKey($viewName, $data);
        $ttl = $ttl ?? self::VIEW_CACHE_TTL;

        return Cache::remember($cacheKey, $ttl, function () use ($viewName, $data) {
            Log::info("Rendering and caching view: {$viewName}");
            return View::make($viewName, $data)->render();
        });
    }

    /**
     * Cache a Blade component.
     */
    public function cacheComponent(string $componentName, array $attributes = [], ?int $ttl = null): string
    {
        $cacheKey = $this->generateComponentKey($componentName, $attributes);
        $ttl = $ttl ?? self::COMPONENT_CACHE_TTL;

        return Cache::remember($cacheKey, $ttl, function () use ($componentName, $attributes) {
            Log::info("Rendering and caching component: {$componentName}");
            
            // Create component instance and render
            $component = View::make("components.{$componentName}", $attributes);
            return $component->render();
        });
    }

    /**
     * Cache static content (like navigation menus, footers).
     */
    public function cacheStaticContent(string $contentKey, callable $renderer, ?int $ttl = null): string
    {
        $cacheKey = self::CACHE_PREFIX . "static:{$contentKey}";
        $ttl = $ttl ?? self::STATIC_CACHE_TTL;

        return Cache::remember($cacheKey, $ttl, function () use ($renderer, $contentKey) {
            Log::info("Rendering and caching static content: {$contentKey}");
            return $renderer();
        });
    }

    /**
     * Cache navigation menu HTML.
     */
    public function cacheNavigationMenu(string $panelType, int $userId, callable $renderer): string
    {
        $cacheKey = self::CACHE_PREFIX . "navigation:{$panelType}:{$userId}";
        
        return Cache::remember($cacheKey, self::STATIC_CACHE_TTL, function () use ($renderer, $panelType) {
            Log::info("Rendering and caching navigation for panel: {$panelType}");
            return $renderer();
        });
    }

    /**
     * Cache dashboard widgets.
     */
    public function cacheDashboardWidget(string $widgetType, int $userId, array $data, callable $renderer): string
    {
        $cacheKey = self::CACHE_PREFIX . "widget:{$widgetType}:{$userId}:" . md5(serialize($data));
        
        return Cache::remember($cacheKey, self::COMPONENT_CACHE_TTL, function () use ($renderer, $widgetType) {
            Log::info("Rendering and caching widget: {$widgetType}");
            return $renderer();
        });
    }

    /**
     * Cache form components.
     */
    public function cacheFormComponent(string $formName, string $componentType, array $config): string
    {
        $cacheKey = self::CACHE_PREFIX . "form:{$formName}:{$componentType}:" . md5(serialize($config));
        
        return Cache::remember($cacheKey, self::COMPONENT_CACHE_TTL, function () use ($formName, $componentType, $config) {
            Log::info("Rendering and caching form component: {$formName}.{$componentType}");
            
            // This would integrate with Filament form rendering
            return View::make("forms.components.{$componentType}", $config)->render();
        });
    }

    /**
     * Cache table components.
     */
    public function cacheTableComponent(string $tableName, string $componentType, array $config): string
    {
        $cacheKey = self::CACHE_PREFIX . "table:{$tableName}:{$componentType}:" . md5(serialize($config));
        
        return Cache::remember($cacheKey, self::COMPONENT_CACHE_TTL, function () use ($tableName, $componentType, $config) {
            Log::info("Rendering and caching table component: {$tableName}.{$componentType}");
            
            return View::make("tables.components.{$componentType}", $config)->render();
        });
    }

    /**
     * Cache email templates.
     */
    public function cacheEmailTemplate(string $templateName, array $data): string
    {
        $cacheKey = self::CACHE_PREFIX . "email:{$templateName}:" . md5(serialize($data));
        
        return Cache::remember($cacheKey, self::STATIC_CACHE_TTL, function () use ($templateName, $data) {
            Log::info("Rendering and caching email template: {$templateName}");
            
            return View::make("emails.{$templateName}", $data)->render();
        });
    }

    /**
     * Cache PDF templates.
     */
    public function cachePdfTemplate(string $templateName, array $data): string
    {
        $cacheKey = self::CACHE_PREFIX . "pdf:{$templateName}:" . md5(serialize($data));
        
        return Cache::remember($cacheKey, self::COMPONENT_CACHE_TTL, function () use ($templateName, $data) {
            Log::info("Rendering and caching PDF template: {$templateName}");
            
            return View::make("pdf.{$templateName}", $data)->render();
        });
    }

    /**
     * Get cached view if exists.
     */
    public function getCachedView(string $viewName, array $data = []): ?string
    {
        $cacheKey = $this->generateViewKey($viewName, $data);
        return Cache::get($cacheKey);
    }

    /**
     * Get cached component if exists.
     */
    public function getCachedComponent(string $componentName, array $attributes = []): ?string
    {
        $cacheKey = $this->generateComponentKey($componentName, $attributes);
        return Cache::get($cacheKey);
    }

    /**
     * Invalidate view cache.
     */
    public function invalidateView(string $viewName, array $data = []): bool
    {
        $cacheKey = $this->generateViewKey($viewName, $data);
        return Cache::forget($cacheKey);
    }

    /**
     * Invalidate component cache.
     */
    public function invalidateComponent(string $componentName, array $attributes = []): bool
    {
        $cacheKey = $this->generateComponentKey($componentName, $attributes);
        return Cache::forget($cacheKey);
    }

    /**
     * Invalidate all view cache for a specific pattern.
     */
    public function invalidateByPattern(string $pattern): void
    {
        $fullPattern = self::CACHE_PREFIX . $pattern;
        Log::info("Invalidating view cache pattern: {$fullPattern}");
        
        // For Redis/Memcached, we'd need to implement pattern-based deletion
        // For now, we'll log the pattern for manual cleanup
    }

    /**
     * Invalidate navigation cache for user.
     */
    public function invalidateNavigationCache(int $userId): void
    {
        $this->invalidateByPattern("navigation:*:{$userId}");
    }

    /**
     * Invalidate widget cache for user.
     */
    public function invalidateWidgetCache(int $userId): void
    {
        $this->invalidateByPattern("widget:*:{$userId}");
    }

    /**
     * Invalidate static content cache.
     */
    public function invalidateStaticContent(string $contentKey): bool
    {
        $cacheKey = self::CACHE_PREFIX . "static:{$contentKey}";
        return Cache::forget($cacheKey);
    }

    /**
     * Clear all view cache.
     */
    public function clearAllViewCache(): void
    {
        Log::info('Clearing all view cache');
        $this->invalidateByPattern('*');
    }

    /**
     * Get view cache statistics.
     */
    public function getCacheStats(): array
    {
        // This would require implementing cache key scanning
        // which is driver-dependent
        return [
            'total_cached_views' => 'N/A',
            'total_cached_components' => 'N/A',
            'cache_hit_rate' => 'N/A',
        ];
    }

    /**
     * Warm up view cache with commonly used views.
     */
    public function warmUpViewCache(): void
    {
        Log::info('Starting view cache warm-up');
        
        try {
            // Cache common static views
            $this->cacheStaticContent('footer', function () {
                return View::make('components.footer')->render();
            });
            
            $this->cacheStaticContent('header', function () {
                return View::make('components.header')->render();
            });
            
            // Cache common navigation menus
            $this->cacheStaticContent('main_navigation', function () {
                return View::make('components.navigation.main')->render();
            });
            
            Log::info('View cache warm-up completed');
        } catch (\Exception $e) {
            Log::error('View cache warm-up failed: ' . $e->getMessage());
        }
    }

    /**
     * Generate cache key for view.
     */
    private function generateViewKey(string $viewName, array $data): string
    {
        $dataHash = md5(serialize($data));
        return self::CACHE_PREFIX . "view:{$viewName}:{$dataHash}";
    }

    /**
     * Generate cache key for component.
     */
    private function generateComponentKey(string $componentName, array $attributes): string
    {
        $attributesHash = md5(serialize($attributes));
        return self::CACHE_PREFIX . "component:{$componentName}:{$attributesHash}";
    }

    /**
     * Check if view should be cached based on configuration.
     */
    public function shouldCacheView(string $viewName): bool
    {
        // Define views that should not be cached
        $excludedViews = [
            'auth.*',
            'errors.*',
            'livewire.*',
            '*.form',
            '*.edit',
        ];
        
        foreach ($excludedViews as $pattern) {
            if (fnmatch($pattern, $viewName)) {
                return false;
            }
        }
        
        return config('app.env') === 'production';
    }

    /**
     * Check if component should be cached.
     */
    public function shouldCacheComponent(string $componentName): bool
    {
        // Define components that should not be cached
        $excludedComponents = [
            'form.*',
            'livewire.*',
            'dynamic.*',
        ];
        
        foreach ($excludedComponents as $pattern) {
            if (fnmatch($pattern, $componentName)) {
                return false;
            }
        }
        
        return config('app.env') === 'production';
    }
}