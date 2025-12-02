<?php

return [

    /*
    |--------------------------------------------------------------------------
    | System Monitoring Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains configuration options for the system monitoring
    | and alerting functionality.
    |
    */

    'enabled' => env('MONITORING_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Health Check Configuration
    |--------------------------------------------------------------------------
    |
    | Configure the system health check intervals and thresholds.
    |
    */

    'health_check' => [
        'interval' => env('HEALTH_CHECK_INTERVAL', 300), // 5 minutes
        'timeout' => env('HEALTH_CHECK_TIMEOUT', 30), // 30 seconds

        'thresholds' => [
            'database_response_time' => env('DB_RESPONSE_THRESHOLD', 1000), // milliseconds
            'cache_response_time' => env('CACHE_RESPONSE_THRESHOLD', 100), // milliseconds
            'disk_usage_warning' => env('DISK_USAGE_WARNING', 80), // percentage
            'disk_usage_critical' => env('DISK_USAGE_CRITICAL', 90), // percentage
            'memory_usage_warning' => env('MEMORY_USAGE_WARNING', 80), // percentage
            'memory_usage_critical' => env('MEMORY_USAGE_CRITICAL', 90), // percentage
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Error Monitoring Configuration
    |--------------------------------------------------------------------------
    |
    | Configure error tracking and alerting thresholds.
    |
    */

    'error_monitoring' => [
        'thresholds' => [
            'critical' => env('ERROR_THRESHOLD_CRITICAL', 1),
            'error' => env('ERROR_THRESHOLD_ERROR', 5),
            'warning' => env('ERROR_THRESHOLD_WARNING', 10),
        ],

        'window' => env('ERROR_MONITORING_WINDOW', 3600), // 1 hour in seconds
    ],

    /*
    |--------------------------------------------------------------------------
    | Performance Monitoring Configuration
    |--------------------------------------------------------------------------
    |
    | Configure performance metric tracking and alerting.
    |
    */

    'performance_monitoring' => [
        'thresholds' => [
            'response_time' => env('RESPONSE_TIME_THRESHOLD', 5000), // milliseconds
            'memory_usage' => env('MEMORY_THRESHOLD', 536870912), // 512MB in bytes
            'query_time' => env('QUERY_TIME_THRESHOLD', 1000), // milliseconds
        ],

        'trend_analysis' => [
            'enabled' => env('TREND_ANALYSIS_ENABLED', true),
            'sample_size' => env('TREND_SAMPLE_SIZE', 100),
            'change_threshold' => env('TREND_CHANGE_THRESHOLD', 20), // percentage
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Alert Configuration
    |--------------------------------------------------------------------------
    |
    | Configure alert recipients and notification channels.
    |
    */

    'alerts' => [
        'cooldown' => env('ALERT_COOLDOWN', 300), // 5 minutes

        'channels' => [
            'email' => env('ALERT_EMAIL_ENABLED', false),
            'slack' => env('ALERT_SLACK_ENABLED', false),
            'log' => env('ALERT_LOG_ENABLED', true),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Alert Recipients
    |--------------------------------------------------------------------------
    |
    | Define who should receive alerts based on severity level.
    |
    */

    'alert_recipients' => [
        'critical' => [
            // Add critical alert recipients here
            // 'admin@example.com',
        ],

        'error' => [
            // Add error alert recipients here
            // 'dev-team@example.com',
        ],

        'warning' => [
            // Add warning alert recipients here
            // 'monitoring@example.com',
        ],

        'default' => [
            // Default recipients for all alerts
            // 'alerts@example.com',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Slack Configuration
    |--------------------------------------------------------------------------
    |
    | Configure Slack webhook settings for alerts.
    |
    */

    'slack' => [
        'webhook_url' => env('SLACK_WEBHOOK_URL'),
        'channel' => env('SLACK_ALERT_CHANNEL', '#alerts'),
        'username' => env('SLACK_ALERT_USERNAME', 'System Monitor'),
        'icon_emoji' => env('SLACK_ALERT_EMOJI', ':warning:'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Retention Configuration
    |--------------------------------------------------------------------------
    |
    | Configure how long to keep monitoring data.
    |
    */

    'retention' => [
        'metrics' => env('METRICS_RETENTION_HOURS', 24), // 24 hours
        'errors' => env('ERROR_RETENTION_HOURS', 168), // 1 week
        'health_checks' => env('HEALTH_CHECK_RETENTION_HOURS', 72), // 3 days
    ],

];
