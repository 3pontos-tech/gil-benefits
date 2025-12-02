<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Performance Optimization Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains configuration options for performance optimization
    | features including caching, asset optimization, and background jobs.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Cache Configuration
    |--------------------------------------------------------------------------
    |
    | Configure cache TTL values and optimization settings.
    |
    */
    'cache' => [
        'default_ttl' => env('CACHE_DEFAULT_TTL', 3600), // 1 hour
        'short_ttl' => env('CACHE_SHORT_TTL', 300), // 5 minutes
        'medium_ttl' => env('CACHE_MEDIUM_TTL', 1800), // 30 minutes
        'long_ttl' => env('CACHE_LONG_TTL', 86400), // 24 hours
        'stats_ttl' => env('CACHE_STATS_TTL', 900), // 15 minutes

        // Cache warmup settings
        'warmup' => [
            'enabled' => env('CACHE_WARMUP_ENABLED', true),
            'schedule' => env('CACHE_WARMUP_SCHEDULE', 'hourly'),
        ],

        // Cache invalidation settings
        'auto_invalidation' => [
            'enabled' => env('CACHE_AUTO_INVALIDATION_ENABLED', true),
            'patterns' => [
                'user' => ['user:*', 'permissions:user:*', 'roles:user:*'],
                'company' => ['company:*'],
                'appointment' => ['appointment_stats:*', 'widget:*', 'dashboard:*'],
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | View Cache Configuration
    |--------------------------------------------------------------------------
    |
    | Configure view caching settings and exclusions.
    |
    */
    'view_cache' => [
        'enabled' => env('VIEW_CACHE_ENABLED', config('app.env') === 'production'),
        'ttl' => env('VIEW_CACHE_TTL', 3600), // 1 hour
        'component_ttl' => env('VIEW_CACHE_COMPONENT_TTL', 1800), // 30 minutes
        'static_ttl' => env('VIEW_CACHE_STATIC_TTL', 86400), // 24 hours

        // Views that should not be cached
        'excluded_views' => [
            'auth.*',
            'errors.*',
            'livewire.*',
            '*.form',
            '*.edit',
        ],

        // Components that should not be cached
        'excluded_components' => [
            'form.*',
            'livewire.*',
            'dynamic.*',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Asset Optimization Configuration
    |--------------------------------------------------------------------------
    |
    | Configure asset optimization and compression settings.
    |
    */
    'assets' => [
        'optimization_enabled' => env('ASSET_OPTIMIZATION_ENABLED', config('app.env') === 'production'),
        'compression_enabled' => env('ASSET_COMPRESSION_ENABLED', true),
        'webp_generation' => env('ASSET_WEBP_GENERATION', true),

        // Image optimization settings
        'image_optimization' => [
            'enabled' => env('IMAGE_OPTIMIZATION_ENABLED', true),
            'quality' => env('IMAGE_OPTIMIZATION_QUALITY', 85),
            'formats' => ['jpg', 'jpeg', 'png', 'gif'],
        ],

        // Asset directories to optimize
        'directories' => [
            'images' => [
                public_path('images'),
                public_path('img'),
                resource_path('images'),
            ],
            'build' => public_path('build'),
        ],

        // Build settings
        'build' => [
            'auto_cleanup' => env('ASSET_AUTO_CLEANUP', true),
            'keep_source_maps' => env('ASSET_KEEP_SOURCE_MAPS', false),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Background Job Configuration
    |--------------------------------------------------------------------------
    |
    | Configure background job processing for performance optimization.
    |
    */
    'jobs' => [
        'enabled' => env('PERFORMANCE_JOBS_ENABLED', true),
        'default_queue' => env('PERFORMANCE_JOBS_QUEUE', 'default'),

        // Queue names for different job types
        'queues' => [
            'cache' => env('CACHE_JOBS_QUEUE', 'cache'),
            'assets' => env('ASSET_JOBS_QUEUE', 'assets'),
            'optimization' => env('OPTIMIZATION_JOBS_QUEUE', 'optimization'),
        ],

        // Job timeouts (in seconds)
        'timeouts' => [
            'cache_warmup' => env('CACHE_WARMUP_TIMEOUT', 300), // 5 minutes
            'asset_optimization' => env('ASSET_OPTIMIZATION_TIMEOUT', 600), // 10 minutes
            'production_optimization' => env('PRODUCTION_OPTIMIZATION_TIMEOUT', 900), // 15 minutes
        ],

        // Job retry settings
        'retries' => [
            'cache_warmup' => env('CACHE_WARMUP_RETRIES', 3),
            'asset_optimization' => env('ASSET_OPTIMIZATION_RETRIES', 2),
            'production_optimization' => env('PRODUCTION_OPTIMIZATION_RETRIES', 2),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Production Optimization Configuration
    |--------------------------------------------------------------------------
    |
    | Configure production-specific optimization settings.
    |
    */
    'production' => [
        'auto_optimize' => env('PRODUCTION_AUTO_OPTIMIZE', false),
        'optimization_schedule' => env('PRODUCTION_OPTIMIZATION_SCHEDULE', 'daily'),

        // Laravel optimizations to enable
        'laravel_optimizations' => [
            'routes' => env('OPTIMIZE_ROUTES', true),
            'config' => env('OPTIMIZE_CONFIG', true),
            'views' => env('OPTIMIZE_VIEWS', true),
            'events' => env('OPTIMIZE_EVENTS', true),
        ],

        // Asset optimizations to enable
        'asset_optimizations' => [
            'build' => env('OPTIMIZE_ASSETS_BUILD', true),
            'images' => env('OPTIMIZE_ASSETS_IMAGES', true),
            'compression' => env('OPTIMIZE_ASSETS_COMPRESSION', true),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Monitoring Configuration
    |--------------------------------------------------------------------------
    |
    | Configure performance monitoring and alerting.
    |
    */
    'monitoring' => [
        'enabled' => env('PERFORMANCE_MONITORING_ENABLED', true),
        'log_slow_operations' => env('LOG_SLOW_OPERATIONS', true),
        'slow_operation_threshold' => env('SLOW_OPERATION_THRESHOLD', 1000), // milliseconds

        // Cache hit rate monitoring
        'cache_monitoring' => [
            'enabled' => env('CACHE_MONITORING_ENABLED', true),
            'low_hit_rate_threshold' => env('CACHE_LOW_HIT_RATE_THRESHOLD', 70), // percentage
        ],

        // Asset size monitoring
        'asset_monitoring' => [
            'enabled' => env('ASSET_MONITORING_ENABLED', true),
            'max_bundle_size' => env('MAX_BUNDLE_SIZE', 1048576), // 1MB in bytes
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Development Configuration
    |--------------------------------------------------------------------------
    |
    | Configure development-specific optimization settings.
    |
    */
    'development' => [
        'disable_optimizations' => env('DEV_DISABLE_OPTIMIZATIONS', true),
        'enable_debug_cache' => env('DEV_ENABLE_DEBUG_CACHE', false),
        'cache_warmup_on_boot' => env('DEV_CACHE_WARMUP_ON_BOOT', false),
    ],

];