<?php

declare(strict_types=1);

use App\Services\Monitoring\DatabaseQueryAnalyzer;
use App\Services\Monitoring\MonitoringDashboard;
use App\Services\Monitoring\PerformanceMetricsCollector;
use App\Services\Monitoring\SystemMonitor;
use App\Services\Monitoring\UserActivityTracker;

test('system monitor can check health', function () {
    $systemMonitor = app(SystemMonitor::class);
    
    $health = $systemMonitor->checkSystemHealth();
    
    expect($health)->toBeArray()
        ->toHaveKeys(['status', 'checks', 'timestamp']);
    
    // Check that all required components are monitored
    expect($health['checks'])->toHaveKeys(['database', 'cache', 'storage', 'memory']);
});

test('performance metrics collector can collect metrics', function () {
    $metricsCollector = app(PerformanceMetricsCollector::class);
    
    $metrics = $metricsCollector->collectMetrics();
    
    expect($metrics)->toBeArray()
        ->toHaveKeys(['timestamp', 'application', 'database', 'memory', 'cache']);
    
    // Check memory metrics structure
    expect($metrics['memory'])->toHaveKeys(['current_usage_mb', 'usage_percentage']);
    expect($metrics['memory']['current_usage_mb'])->toBeNumeric();
    expect($metrics['memory']['usage_percentage'])->toBeNumeric();
});

test('performance metrics collector can record response time', function () {
    $metricsCollector = app(PerformanceMetricsCollector::class);
    
    // Record a response time
    $metricsCollector->recordResponseTime(150.5, 'test.route', 200);
    
    // Collect metrics to see if response time was recorded
    $metrics = $metricsCollector->collectMetrics();
    
    expect($metrics)->toHaveKey('response_times');
    
    // If there are response times recorded, check the structure
    if ($metrics['response_times']['sample_size'] > 0) {
        expect($metrics['response_times'])->toHaveKeys(['average_ms', 'p95_ms', 'p99_ms']);
    }
});

test('database query analyzer can get statistics', function () {
    $queryAnalyzer = app(DatabaseQueryAnalyzer::class);
    
    $stats = $queryAnalyzer->getQueryStatistics();
    
    expect($stats)->toBeArray()
        ->toHaveKeys([
            'total_queries', 
            'average_time', 
            'slow_queries', 
            'slow_query_percentage', 
            'by_type', 
            'by_connection'
        ]);
});

test('user activity tracker can track activity', function () {
    $activityTracker = app(UserActivityTracker::class);
    
    // Track some activity
    $activityTracker->trackActivity(null, 'test_action', ['test' => 'data']);
    
    // Get realtime activity
    $activity = $activityTracker->getRealtimeActivity(10);
    
    expect($activity)->toBeArray();
    
    // If there's activity, check the structure
    if (!empty($activity)) {
        expect($activity[0])->toHaveKeys(['action', 'timestamp', 'context']);
    }
});

test('user activity tracker can get analytics', function () {
    $activityTracker = app(UserActivityTracker::class);
    
    $analytics = $activityTracker->getUserAnalytics(7);
    
    expect($analytics)->toBeArray()
        ->toHaveKeys(['period', 'daily_stats', 'top_actions', 'top_features', 'user_engagement']);
    
    // Check period structure
    expect($analytics['period'])->toHaveKeys(['days', 'start_date', 'end_date']);
    expect($analytics['period']['days'])->toBe(7);
});

test('monitoring dashboard can get dashboard data', function () {
    $dashboard = app(MonitoringDashboard::class);
    
    $data = $dashboard->getDashboardData(false); // Don't use cache for testing
    
    expect($data)->toBeArray()
        ->toHaveKeys([
            'generated_at', 
            'system_health', 
            'performance_metrics', 
            'database_analytics', 
            'user_activity', 
            'alerts', 
            'recommendations'
        ]);
    
    // Check system health structure
    expect($data['system_health'])->toHaveKeys(['overall_status', 'components', 'issues']);
    
    // Check performance metrics structure
    expect($data['performance_metrics'])->toHaveKeys([
        'response_times', 
        'memory_usage', 
        'database_performance', 
        'cache_performance'
    ]);
});

test('monitoring dashboard can get system status', function () {
    $dashboard = app(MonitoringDashboard::class);
    
    $status = $dashboard->getSystemStatus();
    
    expect($status)->toBeArray()
        ->toHaveKeys([
            'status', 
            'timestamp', 
            'version', 
            'environment', 
            'uptime_seconds', 
            'memory_usage_mb', 
            'database_status', 
            'cache_status'
        ]);
    
    expect($status['status'])->toBeString();
    expect($status['timestamp'])->toBeString();
    expect($status['version'])->toBeString();
    expect($status['environment'])->toBeString();
});

test('monitoring dashboard can export report', function () {
    $dashboard = app(MonitoringDashboard::class);
    
    $report = $dashboard->exportMonitoringReport();
    
    expect($report)->toBeArray()
        ->toHaveKeys([
            'generated_at', 
            'report_type', 
            'dashboard_data', 
            'performance_trends', 
            'system_status', 
            'detailed_metrics', 
            'query_analysis', 
            'activity_analytics'
        ]);
    
    expect($report['report_type'])->toBe('comprehensive_monitoring');
});

test('system monitor can record errors', function () {
    $systemMonitor = app(SystemMonitor::class);
    
    // This should not throw an exception
    $systemMonitor->recordError(
        'test_error',
        'This is a test error message',
        ['test_context' => 'test_value'],
        'warning'
    );
    
    expect(true)->toBeTrue(); // If we get here, the method worked
});

test('system monitor can record performance metrics', function () {
    $systemMonitor = app(SystemMonitor::class);
    
    // This should not throw an exception
    $systemMonitor->recordPerformanceMetric(
        'test_metric',
        123.45,
        ['test_context' => 'test_value']
    );
    
    expect(true)->toBeTrue(); // If we get here, the method worked
});
