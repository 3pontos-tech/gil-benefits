<?php

declare(strict_types=1);

namespace App\Services\Monitoring;

use App\Services\Logging\StructuredLogger;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;

class DatabaseQueryAnalyzer
{
    private const CACHE_PREFIX = 'query_analyzer:';
    private const SLOW_QUERY_THRESHOLD = 1000; // 1 second in milliseconds
    private const MAX_STORED_QUERIES = 100;

    private array $queryLog = [];
    private bool $isListening = false;

    public function __construct(
        private readonly StructuredLogger $logger,
        private readonly SystemMonitor $systemMonitor
    ) {}

    /**
     * Start monitoring database queries.
     */
    public function startMonitoring(): void
    {
        if ($this->isListening) {
            return;
        }

        Event::listen(QueryExecuted::class, [$this, 'handleQueryExecuted']);
        $this->isListening = true;

        Log::info('Database query monitoring started');
    }

    /**
     * Stop monitoring database queries.
     */
    public function stopMonitoring(): void
    {
        if (!$this->isListening) {
            return;
        }

        Event::forget(QueryExecuted::class);
        $this->isListening = false;

        Log::info('Database query monitoring stopped');
    }

    /**
     * Handle executed query event.
     */
    public function handleQueryExecuted(QueryExecuted $event): void
    {
        $queryData = [
            'sql' => $event->sql,
            'bindings' => $event->bindings,
            'time' => $event->time,
            'connection' => $event->connectionName,
            'timestamp' => now()->toISOString(),
        ];

        // Add to in-memory log
        $this->queryLog[] = $queryData;

        // Keep only recent queries in memory
        if (count($this->queryLog) > self::MAX_STORED_QUERIES) {
            $this->queryLog = array_slice($this->queryLog, -self::MAX_STORED_QUERIES);
        }

        // Check if query is slow
        if ($event->time >= self::SLOW_QUERY_THRESHOLD) {
            $this->handleSlowQuery($queryData);
        }

        // Store query statistics
        $this->updateQueryStatistics($queryData);

        // Log performance metric
        $this->systemMonitor->recordPerformanceMetric('query_time', $event->time, [
            'connection' => $event->connectionName,
            'query_type' => $this->getQueryType($event->sql),
        ]);
    }

    /**
     * Handle slow query detection.
     */
    private function handleSlowQuery(array $queryData): void
    {
        $slowQueryData = [
            'sql' => $this->sanitizeQuery($queryData['sql']),
            'time_ms' => $queryData['time'],
            'connection' => $queryData['connection'],
            'timestamp' => $queryData['timestamp'],
            'query_hash' => md5($queryData['sql']),
        ];

        // Store slow query
        $this->storeSlowQuery($slowQueryData);

        // Log slow query
        $this->logger->logPerformanceEvent('slow_query_detected', $queryData['time'] / 1000, $slowQueryData);

        // Trigger alert for very slow queries (>5 seconds)
        if ($queryData['time'] >= 5000) {
            $this->systemMonitor->recordError(
                'very_slow_query',
                'Query execution time exceeded 5 seconds',
                $slowQueryData,
                'warning'
            );
        }
    }

    /**
     * Store slow query for analysis.
     */
    private function storeSlowQuery(array $queryData): void
    {
        $key = self::CACHE_PREFIX . 'slow_queries';
        $slowQueries = Cache::get($key, []);

        $slowQueries[] = $queryData;

        // Keep only last 50 slow queries
        if (count($slowQueries) > 50) {
            $slowQueries = array_slice($slowQueries, -50);
        }

        Cache::put($key, $slowQueries, 3600); // Store for 1 hour
    }

    /**
     * Update query statistics.
     */
    private function updateQueryStatistics(array $queryData): void
    {
        $queryType = $this->getQueryType($queryData['sql']);
        $statsKey = self::CACHE_PREFIX . 'stats:' . now()->format('Y-m-d-H');

        $stats = Cache::get($statsKey, [
            'total_queries' => 0,
            'total_time' => 0,
            'by_type' => [],
            'by_connection' => [],
            'slow_queries' => 0,
        ]);

        // Update overall stats
        $stats['total_queries']++;
        $stats['total_time'] += $queryData['time'];

        // Update by query type
        if (!isset($stats['by_type'][$queryType])) {
            $stats['by_type'][$queryType] = ['count' => 0, 'total_time' => 0];
        }
        $stats['by_type'][$queryType]['count']++;
        $stats['by_type'][$queryType]['total_time'] += $queryData['time'];

        // Update by connection
        $connection = $queryData['connection'];
        if (!isset($stats['by_connection'][$connection])) {
            $stats['by_connection'][$connection] = ['count' => 0, 'total_time' => 0];
        }
        $stats['by_connection'][$connection]['count']++;
        $stats['by_connection'][$connection]['total_time'] += $queryData['time'];

        // Update slow query count
        if ($queryData['time'] >= self::SLOW_QUERY_THRESHOLD) {
            $stats['slow_queries']++;
        }

        Cache::put($statsKey, $stats, 3600); // Store for 1 hour
    }

    /**
     * Get query type from SQL.
     */
    private function getQueryType(string $sql): string
    {
        $sql = trim(strtoupper($sql));
        
        if (str_starts_with($sql, 'SELECT')) {
            return 'SELECT';
        } elseif (str_starts_with($sql, 'INSERT')) {
            return 'INSERT';
        } elseif (str_starts_with($sql, 'UPDATE')) {
            return 'UPDATE';
        } elseif (str_starts_with($sql, 'DELETE')) {
            return 'DELETE';
        } elseif (str_starts_with($sql, 'CREATE')) {
            return 'CREATE';
        } elseif (str_starts_with($sql, 'ALTER')) {
            return 'ALTER';
        } elseif (str_starts_with($sql, 'DROP')) {
            return 'DROP';
        }

        return 'OTHER';
    }

    /**
     * Sanitize query for logging (remove sensitive data).
     */
    private function sanitizeQuery(string $sql): string
    {
        // Replace potential sensitive data with placeholders
        $sql = preg_replace('/\b\d{11,}\b/', '[LARGE_NUMBER]', $sql); // Large numbers (could be IDs)
        $sql = preg_replace('/\'[^\']{20,}\'/', '[LONG_STRING]', $sql); // Long strings
        $sql = preg_replace('/\b[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Z|a-z]{2,}\b/', '[EMAIL]', $sql); // Email addresses
        
        return $sql;
    }

    /**
     * Get current query statistics.
     */
    public function getQueryStatistics(): array
    {
        $currentHour = now()->format('Y-m-d-H');
        $statsKey = self::CACHE_PREFIX . 'stats:' . $currentHour;
        
        $stats = Cache::get($statsKey, [
            'total_queries' => 0,
            'total_time' => 0,
            'by_type' => [],
            'by_connection' => [],
            'slow_queries' => 0,
        ]);

        // Calculate averages
        if ($stats['total_queries'] > 0) {
            $stats['average_time'] = round($stats['total_time'] / $stats['total_queries'], 2);
            $stats['slow_query_percentage'] = round(($stats['slow_queries'] / $stats['total_queries']) * 100, 2);
        } else {
            $stats['average_time'] = 0;
            $stats['slow_query_percentage'] = 0;
        }

        return $stats;
    }

    /**
     * Get slow queries from the last hour.
     */
    public function getSlowQueries(): array
    {
        $key = self::CACHE_PREFIX . 'slow_queries';
        return Cache::get($key, []);
    }

    /**
     * Get query statistics for the last N hours.
     */
    public function getHistoricalStatistics(int $hours = 24): array
    {
        $statistics = [];
        $endTime = now();

        for ($i = 0; $i < $hours; $i++) {
            $time = $endTime->copy()->subHours($i);
            $statsKey = self::CACHE_PREFIX . 'stats:' . $time->format('Y-m-d-H');
            $hourlyStats = Cache::get($statsKey);

            if ($hourlyStats) {
                $statistics[$time->format('Y-m-d H:00')] = $hourlyStats;
            }
        }

        return array_reverse($statistics, true);
    }

    /**
     * Analyze query patterns and provide recommendations.
     */
    public function analyzeQueryPatterns(): array
    {
        $stats = $this->getQueryStatistics();
        $slowQueries = $this->getSlowQueries();
        $recommendations = [];

        // Check for high slow query percentage
        if ($stats['slow_query_percentage'] > 10) {
            $recommendations[] = [
                'type' => 'performance',
                'priority' => 'high',
                'issue' => 'High percentage of slow queries',
                'description' => "Currently {$stats['slow_query_percentage']}% of queries are slow (>{self::SLOW_QUERY_THRESHOLD}ms)",
                'recommendation' => 'Review and optimize slow queries, consider adding indexes',
            ];
        }

        // Check for excessive SELECT queries
        if (isset($stats['by_type']['SELECT']) && $stats['by_type']['SELECT']['count'] > 1000) {
            $avgTime = round($stats['by_type']['SELECT']['total_time'] / $stats['by_type']['SELECT']['count'], 2);
            $recommendations[] = [
                'type' => 'optimization',
                'priority' => 'medium',
                'issue' => 'High number of SELECT queries',
                'description' => "Executed {$stats['by_type']['SELECT']['count']} SELECT queries with average time of {$avgTime}ms",
                'recommendation' => 'Consider implementing caching or query optimization',
            ];
        }

        // Analyze slow query patterns
        $queryPatterns = $this->analyzeSlowQueryPatterns($slowQueries);
        foreach ($queryPatterns as $pattern) {
            $recommendations[] = [
                'type' => 'query_optimization',
                'priority' => 'high',
                'issue' => 'Repeated slow query pattern',
                'description' => "Query pattern appears {$pattern['count']} times with average time {$pattern['avg_time']}ms",
                'recommendation' => 'Optimize this specific query pattern: ' . substr($pattern['pattern'], 0, 100) . '...',
            ];
        }

        return $recommendations;
    }

    /**
     * Analyze patterns in slow queries.
     */
    private function analyzeSlowQueryPatterns(array $slowQueries): array
    {
        $patterns = [];

        foreach ($slowQueries as $query) {
            // Create a pattern by removing specific values
            $pattern = preg_replace('/\d+/', '?', $query['sql']);
            $pattern = preg_replace('/\'[^\']*\'/', '?', $pattern);
            $pattern = preg_replace('/\s+/', ' ', $pattern);

            $hash = md5($pattern);

            if (!isset($patterns[$hash])) {
                $patterns[$hash] = [
                    'pattern' => $pattern,
                    'count' => 0,
                    'total_time' => 0,
                    'queries' => [],
                ];
            }

            $patterns[$hash]['count']++;
            $patterns[$hash]['total_time'] += $query['time_ms'];
            $patterns[$hash]['queries'][] = $query;
        }

        // Calculate averages and filter significant patterns
        $significantPatterns = [];
        foreach ($patterns as $pattern) {
            if ($pattern['count'] >= 3) { // Only patterns that appear 3+ times
                $pattern['avg_time'] = round($pattern['total_time'] / $pattern['count'], 2);
                $significantPatterns[] = $pattern;
            }
        }

        // Sort by frequency and average time
        usort($significantPatterns, function ($a, $b) {
            return ($b['count'] * $b['avg_time']) <=> ($a['count'] * $a['avg_time']);
        });

        return array_slice($significantPatterns, 0, 5); // Return top 5 patterns
    }

    /**
     * Get current in-memory query log.
     */
    public function getCurrentQueryLog(): array
    {
        return $this->queryLog;
    }

    /**
     * Clear query statistics and logs.
     */
    public function clearStatistics(): void
    {
        $this->queryLog = [];
        
        // Clear cached statistics
        $pattern = self::CACHE_PREFIX . '*';
        $keys = Cache::getRedis()->keys($pattern);
        
        if (!empty($keys)) {
            Cache::getRedis()->del($keys);
        }

        Log::info('Query analyzer statistics cleared');
    }

    /**
     * Export query analysis report.
     */
    public function exportAnalysisReport(): array
    {
        return [
            'generated_at' => now()->toISOString(),
            'monitoring_status' => $this->isListening ? 'active' : 'inactive',
            'current_statistics' => $this->getQueryStatistics(),
            'slow_queries' => $this->getSlowQueries(),
            'historical_data' => $this->getHistoricalStatistics(24),
            'recommendations' => $this->analyzeQueryPatterns(),
            'configuration' => [
                'slow_query_threshold_ms' => self::SLOW_QUERY_THRESHOLD,
                'max_stored_queries' => self::MAX_STORED_QUERIES,
            ],
        ];
    }
}