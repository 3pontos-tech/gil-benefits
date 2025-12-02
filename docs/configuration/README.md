# Configuration Documentation

## Overview

This document provides comprehensive documentation for all configuration files in the Laravel application. Each configuration file is explained with its purpose, available options, and usage examples.

## Configuration Files

### Core Laravel Configuration

#### `config/app.php`
Standard Laravel application configuration including environment settings, service providers, and aliases.

**Key Settings:**
- `APP_NAME`: Application name displayed in UI
- `APP_ENV`: Environment (local, staging, production)
- `APP_DEBUG`: Debug mode toggle
- `APP_URL`: Base application URL

#### `config/database.php`
Database connection configuration.

**Default Connection:** SQLite (`identifier.sqlite`)

#### `config/cache.php`
Cache configuration with multiple drivers support.

**Default Driver:** File-based caching

### Application-Specific Configuration

#### `config/app-modules.php`
Configuration for the modular architecture system.

```php
<?php
return [
    // PHP namespace for modules
    'modules_namespace' => 'TresPontosTech',
    
    // Composer vendor name for modules
    'modules_vendor' => '3pontos-tech',
    
    // Directory where modules are stored
    'modules_directory' => 'app-modules',
    
    // Base test case class for module tests
    'tests_base' => Tests\TestCase::class,
    
    // Custom stubs for module generation
    'stubs' => null,
    
    // Event discovery configuration
    'should_discover_events' => true,
];
```

**Usage Examples:**

```bash
# Create a new module
php artisan make:module Billing

# This will create:
# app-modules/billing/
# ├── composer.json (with 3pontos-tech/billing package name)
# ├── src/
# │   └── Providers/BillingServiceProvider.php (TresPontosTech\Billing namespace)
# └── tests/ (extending Tests\TestCase)
```

**Environment Variables:**
- None (uses hardcoded values for consistency)

#### `config/monitoring.php`
System monitoring and alerting configuration.

```php
<?php
return [
    // Enable/disable monitoring system
    'enabled' => env('MONITORING_ENABLED', true),
    
    // Health check configuration
    'health_check' => [
        'interval' => env('HEALTH_CHECK_INTERVAL', 300), // 5 minutes
        'timeout' => env('HEALTH_CHECK_TIMEOUT', 30),    // 30 seconds
        
        'thresholds' => [
            'database_response_time' => env('DB_RESPONSE_THRESHOLD', 1000),     // ms
            'cache_response_time' => env('CACHE_RESPONSE_THRESHOLD', 100),      // ms
            'disk_usage_warning' => env('DISK_USAGE_WARNING', 80),             // %
            'disk_usage_critical' => env('DISK_USAGE_CRITICAL', 90),           // %
            'memory_usage_warning' => env('MEMORY_USAGE_WARNING', 80),         // %
            'memory_usage_critical' => env('MEMORY_USAGE_CRITICAL', 90),       // %
        ],
    ],
    
    // Error monitoring thresholds
    'error_monitoring' => [
        'thresholds' => [
            'critical' => env('ERROR_THRESHOLD_CRITICAL', 1),
            'error' => env('ERROR_THRESHOLD_ERROR', 5),
            'warning' => env('ERROR_THRESHOLD_WARNING', 10),
        ],
        'window' => env('ERROR_MONITORING_WINDOW', 3600), // 1 hour
    ],
    
    // Performance monitoring
    'performance_monitoring' => [
        'thresholds' => [
            'response_time' => env('RESPONSE_TIME_THRESHOLD', 5000),  // ms
            'memory_usage' => env('MEMORY_THRESHOLD', 536870912),     // 512MB
            'query_time' => env('QUERY_TIME_THRESHOLD', 1000),       // ms
        ],
        
        'trend_analysis' => [
            'enabled' => env('TREND_ANALYSIS_ENABLED', true),
            'sample_size' => env('TREND_SAMPLE_SIZE', 100),
            'change_threshold' => env('TREND_CHANGE_THRESHOLD', 20), // %
        ],
    ],
    
    // Alert configuration
    'alerts' => [
        'cooldown' => env('ALERT_COOLDOWN', 300), // 5 minutes
        
        'channels' => [
            'email' => env('ALERT_EMAIL_ENABLED', false),
            'slack' => env('ALERT_SLACK_ENABLED', false),
            'log' => env('ALERT_LOG_ENABLED', true),
        ],
    ],
    
    // Alert recipients by severity
    'alert_recipients' => [
        'critical' => [
            // 'admin@example.com',
        ],
        'error' => [
            // 'dev-team@example.com',
        ],
        'warning' => [
            // 'monitoring@example.com',
        ],
        'default' => [
            // 'alerts@example.com',
        ],
    ],
    
    // Slack configuration
    'slack' => [
        'webhook_url' => env('SLACK_WEBHOOK_URL'),
        'channel' => env('SLACK_ALERT_CHANNEL', '#alerts'),
        'username' => env('SLACK_ALERT_USERNAME', 'System Monitor'),
        'icon_emoji' => env('SLACK_ALERT_EMOJI', ':warning:'),
    ],
    
    // Data retention settings
    'retention' => [
        'metrics' => env('METRICS_RETENTION_HOURS', 24),        // 24 hours
        'errors' => env('ERROR_RETENTION_HOURS', 168),          // 1 week
        'health_checks' => env('HEALTH_CHECK_RETENTION_HOURS', 72), // 3 days
    ],
];
```

**Usage Examples:**

```php
// Check if monitoring is enabled
if (config('monitoring.enabled')) {
    // Perform monitoring tasks
}

// Get database response threshold
$threshold = config('monitoring.health_check.thresholds.database_response_time');

// Get alert recipients for critical errors
$recipients = config('monitoring.alert_recipients.critical');
```

**Environment Variables:**
```env
# Basic monitoring
MONITORING_ENABLED=true
HEALTH_CHECK_INTERVAL=300
HEALTH_CHECK_TIMEOUT=30

# Performance thresholds
DB_RESPONSE_THRESHOLD=1000
CACHE_RESPONSE_THRESHOLD=100
RESPONSE_TIME_THRESHOLD=5000
MEMORY_THRESHOLD=536870912
QUERY_TIME_THRESHOLD=1000

# Resource usage thresholds
DISK_USAGE_WARNING=80
DISK_USAGE_CRITICAL=90
MEMORY_USAGE_WARNING=80
MEMORY_USAGE_CRITICAL=90

# Error monitoring
ERROR_THRESHOLD_CRITICAL=1
ERROR_THRESHOLD_ERROR=5
ERROR_THRESHOLD_WARNING=10
ERROR_MONITORING_WINDOW=3600

# Trend analysis
TREND_ANALYSIS_ENABLED=true
TREND_SAMPLE_SIZE=100
TREND_CHANGE_THRESHOLD=20

# Alerts
ALERT_COOLDOWN=300
ALERT_EMAIL_ENABLED=false
ALERT_SLACK_ENABLED=false
ALERT_LOG_ENABLED=true

# Slack integration
SLACK_WEBHOOK_URL=https://hooks.slack.com/services/...
SLACK_ALERT_CHANNEL=#alerts
SLACK_ALERT_USERNAME="System Monitor"
SLACK_ALERT_EMOJI=:warning:

# Data retention
METRICS_RETENTION_HOURS=24
ERROR_RETENTION_HOURS=168
HEALTH_CHECK_RETENTION_HOURS=72
```

#### `config/performance.php`
Performance optimization and caching configuration.

```php
<?php
return [
    // Cache configuration
    'cache' => [
        'default_ttl' => env('CACHE_DEFAULT_TTL', 3600),    // 1 hour
        'short_ttl' => env('CACHE_SHORT_TTL', 300),         // 5 minutes
        'medium_ttl' => env('CACHE_MEDIUM_TTL', 1800),      // 30 minutes
        'long_ttl' => env('CACHE_LONG_TTL', 86400),         // 24 hours
        'stats_ttl' => env('CACHE_STATS_TTL', 900),         // 15 minutes
        
        // Cache warmup
        'warmup' => [
            'enabled' => env('CACHE_WARMUP_ENABLED', true),
            'schedule' => env('CACHE_WARMUP_SCHEDULE', 'hourly'),
        ],
        
        // Auto invalidation patterns
        'auto_invalidation' => [
            'enabled' => env('CACHE_AUTO_INVALIDATION_ENABLED', true),
            'patterns' => [
                'user' => ['user:*', 'permissions:user:*', 'roles:user:*'],
                'company' => ['company:*'],
                'appointment' => ['appointment_stats:*', 'widget:*', 'dashboard:*'],
            ],
        ],
    ],
    
    // View caching
    'view_cache' => [
        'enabled' => env('VIEW_CACHE_ENABLED', config('app.env') === 'production'),
        'ttl' => env('VIEW_CACHE_TTL', 3600),                    // 1 hour
        'component_ttl' => env('VIEW_CACHE_COMPONENT_TTL', 1800), // 30 minutes
        'static_ttl' => env('VIEW_CACHE_STATIC_TTL', 86400),     // 24 hours
        
        // Excluded views (not cached)
        'excluded_views' => [
            'auth.*',
            'errors.*',
            'livewire.*',
            '*.form',
            '*.edit',
        ],
        
        // Excluded components (not cached)
        'excluded_components' => [
            'form.*',
            'livewire.*',
            'dynamic.*',
        ],
    ],
    
    // Asset optimization
    'assets' => [
        'optimization_enabled' => env('ASSET_OPTIMIZATION_ENABLED', config('app.env') === 'production'),
        'compression_enabled' => env('ASSET_COMPRESSION_ENABLED', true),
        'webp_generation' => env('ASSET_WEBP_GENERATION', true),
        
        // Image optimization
        'image_optimization' => [
            'enabled' => env('IMAGE_OPTIMIZATION_ENABLED', true),
            'quality' => env('IMAGE_OPTIMIZATION_QUALITY', 85),
            'formats' => ['jpg', 'jpeg', 'png', 'gif'],
        ],
        
        // Asset directories
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
    
    // Background jobs
    'jobs' => [
        'enabled' => env('PERFORMANCE_JOBS_ENABLED', true),
        'default_queue' => env('PERFORMANCE_JOBS_QUEUE', 'default'),
        
        // Queue names
        'queues' => [
            'cache' => env('CACHE_JOBS_QUEUE', 'cache'),
            'assets' => env('ASSET_JOBS_QUEUE', 'assets'),
            'optimization' => env('OPTIMIZATION_JOBS_QUEUE', 'optimization'),
        ],
        
        // Job timeouts (seconds)
        'timeouts' => [
            'cache_warmup' => env('CACHE_WARMUP_TIMEOUT', 300),           // 5 minutes
            'asset_optimization' => env('ASSET_OPTIMIZATION_TIMEOUT', 600), // 10 minutes
            'production_optimization' => env('PRODUCTION_OPTIMIZATION_TIMEOUT', 900), // 15 minutes
        ],
        
        // Job retries
        'retries' => [
            'cache_warmup' => env('CACHE_WARMUP_RETRIES', 3),
            'asset_optimization' => env('ASSET_OPTIMIZATION_RETRIES', 2),
            'production_optimization' => env('PRODUCTION_OPTIMIZATION_RETRIES', 2),
        ],
    ],
    
    // Production optimizations
    'production' => [
        'auto_optimize' => env('PRODUCTION_AUTO_OPTIMIZE', false),
        'optimization_schedule' => env('PRODUCTION_OPTIMIZATION_SCHEDULE', 'daily'),
        
        // Laravel optimizations
        'laravel_optimizations' => [
            'routes' => env('OPTIMIZE_ROUTES', true),
            'config' => env('OPTIMIZE_CONFIG', true),
            'views' => env('OPTIMIZE_VIEWS', true),
            'events' => env('OPTIMIZE_EVENTS', true),
        ],
        
        // Asset optimizations
        'asset_optimizations' => [
            'build' => env('OPTIMIZE_ASSETS_BUILD', true),
            'images' => env('OPTIMIZE_ASSETS_IMAGES', true),
            'compression' => env('OPTIMIZE_ASSETS_COMPRESSION', true),
        ],
    ],
    
    // Performance monitoring
    'monitoring' => [
        'enabled' => env('PERFORMANCE_MONITORING_ENABLED', true),
        'log_slow_operations' => env('LOG_SLOW_OPERATIONS', true),
        'slow_operation_threshold' => env('SLOW_OPERATION_THRESHOLD', 1000), // ms
        
        // Cache monitoring
        'cache_monitoring' => [
            'enabled' => env('CACHE_MONITORING_ENABLED', true),
            'low_hit_rate_threshold' => env('CACHE_LOW_HIT_RATE_THRESHOLD', 70), // %
        ],
        
        // Asset monitoring
        'asset_monitoring' => [
            'enabled' => env('ASSET_MONITORING_ENABLED', true),
            'max_bundle_size' => env('MAX_BUNDLE_SIZE', 1048576), // 1MB
        ],
    ],
    
    // Development settings
    'development' => [
        'disable_optimizations' => env('DEV_DISABLE_OPTIMIZATIONS', true),
        'enable_debug_cache' => env('DEV_ENABLE_DEBUG_CACHE', false),
        'cache_warmup_on_boot' => env('DEV_CACHE_WARMUP_ON_BOOT', false),
    ],
];
```

**Usage Examples:**

```php
// Get cache TTL for different data types
$userCacheTtl = config('performance.cache.medium_ttl'); // 30 minutes
$statsCacheTtl = config('performance.cache.stats_ttl'); // 15 minutes

// Check if view caching is enabled
if (config('performance.view_cache.enabled')) {
    // Enable view caching
}

// Get asset optimization settings
$imageQuality = config('performance.assets.image_optimization.quality'); // 85

// Check if performance jobs are enabled
if (config('performance.jobs.enabled')) {
    // Dispatch performance optimization jobs
}

// Get cache invalidation patterns
$patterns = config('performance.cache.auto_invalidation.patterns.user');
// Returns: ['user:*', 'permissions:user:*', 'roles:user:*']
```

**Environment Variables:**
```env
# Cache settings
CACHE_DEFAULT_TTL=3600
CACHE_SHORT_TTL=300
CACHE_MEDIUM_TTL=1800
CACHE_LONG_TTL=86400
CACHE_STATS_TTL=900

# Cache warmup
CACHE_WARMUP_ENABLED=true
CACHE_WARMUP_SCHEDULE=hourly

# Cache invalidation
CACHE_AUTO_INVALIDATION_ENABLED=true

# View caching
VIEW_CACHE_ENABLED=true
VIEW_CACHE_TTL=3600
VIEW_CACHE_COMPONENT_TTL=1800
VIEW_CACHE_STATIC_TTL=86400

# Asset optimization
ASSET_OPTIMIZATION_ENABLED=true
ASSET_COMPRESSION_ENABLED=true
ASSET_WEBP_GENERATION=true

# Image optimization
IMAGE_OPTIMIZATION_ENABLED=true
IMAGE_OPTIMIZATION_QUALITY=85

# Asset build
ASSET_AUTO_CLEANUP=true
ASSET_KEEP_SOURCE_MAPS=false

# Performance jobs
PERFORMANCE_JOBS_ENABLED=true
PERFORMANCE_JOBS_QUEUE=default
CACHE_JOBS_QUEUE=cache
ASSET_JOBS_QUEUE=assets
OPTIMIZATION_JOBS_QUEUE=optimization

# Job timeouts
CACHE_WARMUP_TIMEOUT=300
ASSET_OPTIMIZATION_TIMEOUT=600
PRODUCTION_OPTIMIZATION_TIMEOUT=900

# Job retries
CACHE_WARMUP_RETRIES=3
ASSET_OPTIMIZATION_RETRIES=2
PRODUCTION_OPTIMIZATION_RETRIES=2

# Production optimization
PRODUCTION_AUTO_OPTIMIZE=false
PRODUCTION_OPTIMIZATION_SCHEDULE=daily

# Laravel optimizations
OPTIMIZE_ROUTES=true
OPTIMIZE_CONFIG=true
OPTIMIZE_VIEWS=true
OPTIMIZE_EVENTS=true

# Asset optimizations
OPTIMIZE_ASSETS_BUILD=true
OPTIMIZE_ASSETS_IMAGES=true
OPTIMIZE_ASSETS_COMPRESSION=true

# Performance monitoring
PERFORMANCE_MONITORING_ENABLED=true
LOG_SLOW_OPERATIONS=true
SLOW_OPERATION_THRESHOLD=1000

# Cache monitoring
CACHE_MONITORING_ENABLED=true
CACHE_LOW_HIT_RATE_THRESHOLD=70

# Asset monitoring
ASSET_MONITORING_ENABLED=true
MAX_BUNDLE_SIZE=1048576

# Development settings
DEV_DISABLE_OPTIMIZATIONS=true
DEV_ENABLE_DEBUG_CACHE=false
DEV_CACHE_WARMUP_ON_BOOT=false
```

### Filament Configuration

#### `config/filament-*.php`
Filament panel configurations for different user interfaces.

**Available Panels:**
- `filament-admin.php` - Admin panel configuration
- `filament-app.php` - User panel configuration  
- `filament-company.php` - Company panel configuration
- `filament-consultant.php` - Consultant panel configuration
- `filament-guest.php` - Guest/public panel configuration

**Common Configuration Options:**
```php
<?php
return [
    'id' => 'admin',
    'path' => '/admin',
    'login' => \App\Filament\Shared\Pages\Login::class,
    'colors' => [
        'primary' => Color::Blue,
    ],
    'discoverResources' => [
        app_path('Filament/Admin/Resources'),
        // Module resources are auto-discovered
    ],
    'discoverPages' => [
        app_path('Filament/Admin/Pages'),
    ],
    'discoverWidgets' => [
        app_path('Filament/Admin/Widgets'),
    ],
    'middleware' => [
        'web',
        'auth:web',
    ],
    'authMiddleware' => [
        'auth',
    ],
];
```

### Third-Party Service Configuration

#### `config/cashier.php`
Laravel Cashier (Stripe) configuration for billing.

#### `config/permission.php`
Spatie Laravel Permission configuration for role-based access control.

#### `config/media-library.php`
Spatie Media Library configuration for file management.

## Environment Configuration

### Required Environment Variables

```env
# Application
APP_NAME="Your App Name"
APP_ENV=local
APP_KEY=base64:...
APP_DEBUG=true
APP_URL=http://localhost

# Database
DB_CONNECTION=sqlite
DB_DATABASE=identifier.sqlite

# Cache
CACHE_DRIVER=file

# Session
SESSION_DRIVER=file

# Queue
QUEUE_CONNECTION=sync

# Mail
MAIL_MAILER=smtp
MAIL_HOST=mailpit
MAIL_PORT=1025
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS="hello@example.com"
MAIL_FROM_NAME="${APP_NAME}"

# Stripe (for billing)
STRIPE_KEY=pk_test_...
STRIPE_SECRET=sk_test_...
STRIPE_WEBHOOK_SECRET=whsec_...

# Resend (for email)
RESEND_API_KEY=re_...
```

### Optional Environment Variables

```env
# Monitoring
MONITORING_ENABLED=true
HEALTH_CHECK_INTERVAL=300
DB_RESPONSE_THRESHOLD=1000

# Performance
CACHE_DEFAULT_TTL=3600
VIEW_CACHE_ENABLED=true
ASSET_OPTIMIZATION_ENABLED=true

# Development
DEV_DISABLE_OPTIMIZATIONS=true
DEV_ENABLE_DEBUG_CACHE=false
```

## Configuration Best Practices

### 1. Environment-Specific Settings
```php
// Use environment-specific defaults
'enabled' => env('FEATURE_ENABLED', config('app.env') === 'production'),
```

### 2. Validation
```php
// Validate configuration values
'timeout' => max(1, (int) env('TIMEOUT', 30)),
```

### 3. Fallback Values
```php
// Provide sensible defaults
'cache_ttl' => env('CACHE_TTL', 3600),
```

### 4. Documentation
```php
// Document configuration options
/*
|--------------------------------------------------------------------------
| Cache Time-To-Live
|--------------------------------------------------------------------------
|
| This value determines how long cached data should be stored before
| expiring. The value is in seconds.
|
*/
'cache_ttl' => env('CACHE_TTL', 3600),
```

## Configuration Usage in Code

### Accessing Configuration
```php
// Get configuration value
$value = config('monitoring.enabled');

// Get with default
$timeout = config('monitoring.health_check.timeout', 30);

// Get entire configuration array
$monitoring = config('monitoring');
```

### Setting Configuration at Runtime
```php
// Set configuration value
config(['monitoring.enabled' => false]);

// Set multiple values
config([
    'monitoring.enabled' => true,
    'monitoring.health_check.timeout' => 60,
]);
```

### Configuration in Service Providers
```php
public function boot(): void
{
    // Merge configuration
    $this->mergeConfigFrom(
        __DIR__ . '/../../config/monitoring.php',
        'monitoring'
    );
    
    // Publish configuration
    $this->publishes([
        __DIR__ . '/../../config/monitoring.php' => config_path('monitoring.php'),
    ], 'config');
}
```

## Troubleshooting

### Common Issues

1. **Configuration not loading**
   - Clear configuration cache: `php artisan config:clear`
   - Rebuild cache: `php artisan config:cache`

2. **Environment variables not working**
   - Check `.env` file exists and is readable
   - Verify variable names match exactly
   - Clear configuration cache after changes

3. **Module configuration not found**
   - Ensure module service provider is registered
   - Check module configuration is published
   - Verify configuration file paths

### Debugging Configuration
```php
// Dump all configuration
dd(config());

// Dump specific configuration
dd(config('monitoring'));

// Check if configuration exists
if (config()->has('monitoring.enabled')) {
    // Configuration exists
}
```