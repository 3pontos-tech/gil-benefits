<?php

namespace App\Services;

use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class QueryOptimizationService
{
    /**
     * @var array<int, array<string, mixed>>
     */
    private array $queries = [];

    /**
     * @var array<int, array<string, mixed>>
     */
    private array $slowQueries = [];

    private float $slowQueryThreshold = 100.0; // milliseconds

    public function __construct()
    {
        $this->enableQueryLogging();
    }

    /**
     * Enable query logging and monitoring.
     */
    public function enableQueryLogging(): void
    {
        DB::listen(function (QueryExecuted $query) {
            $this->logQuery($query);
        });
    }

    /**
     * Log executed query for analysis.
     */
    private function logQuery(QueryExecuted $query): void
    {
        $executionTime = $query->time;
        $sql = $query->sql;
        $bindings = $query->bindings;

        $queryData = [
            'sql' => $sql,
            'bindings' => $bindings,
            'time' => $executionTime,
            'connection' => $query->connectionName,
            'timestamp' => now(),
        ];

        $this->queries[] = $queryData;

        // Log slow queries
        if ($executionTime > $this->slowQueryThreshold) {
            $this->slowQueries[] = $queryData;
            $this->logSlowQuery($queryData);
        }

        // Detect N+1 queries
        if ($this->isLikelyNPlusOneQuery($sql)) {
            $this->logNPlusOneQuery($queryData);
        }
    }

    /**
     * Log slow query for monitoring.
     */
    private function logSlowQuery(array $queryData): void
    {
        Log::warning('Slow query detected', [
            'sql' => $queryData['sql'],
            'time' => $queryData['time'] . 'ms',
            'bindings' => $queryData['bindings'],
            'connection' => $queryData['connection'],
        ]);
    }

    /**
     * Detect potential N+1 query patterns.
     */
    private function isLikelyNPlusOneQuery(string $sql): bool
    {
        // Simple heuristic: SELECT queries with WHERE id = ? pattern
        return preg_match('/select.*where.*id\s*=\s*\?/i', $sql) === 1;
    }

    /**
     * Log potential N+1 query.
     */
    private function logNPlusOneQuery(array $queryData): void
    {
        Log::warning('Potential N+1 query detected', [
            'sql' => $queryData['sql'],
            'time' => $queryData['time'] . 'ms',
            'suggestion' => 'Consider using eager loading with ->with() method',
        ]);
    }

    /**
     * Get query statistics.
     *
     * @return array<string, mixed>
     */
    public function getQueryStats(): array
    {
        $totalQueries = count($this->queries);
        $totalTime = array_sum(array_column($this->queries, 'time'));
        $slowQueriesCount = count($this->slowQueries);

        return [
            'total_queries' => $totalQueries,
            'total_time' => round($totalTime, 2) . 'ms',
            'average_time' => $totalQueries > 0 ? round($totalTime / $totalQueries, 2) . 'ms' : '0ms',
            'slow_queries_count' => $slowQueriesCount,
            'slow_queries_percentage' => $totalQueries > 0 ? round(($slowQueriesCount / $totalQueries) * 100, 2) . '%' : '0%',
        ];
    }

    /**
     * Get slow queries for analysis.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getSlowQueries(): array
    {
        return $this->slowQueries;
    }

    /**
     * Get all queries for analysis.
     */
    public function getAllQueries(): array
    {
        return $this->queries;
    }

    /**
     * Clear query logs.
     */
    public function clearLogs(): void
    {
        $this->queries = [];
        $this->slowQueries = [];
    }

    /**
     * Set slow query threshold in milliseconds.
     */
    public function setSlowQueryThreshold(float $threshold): void
    {
        $this->slowQueryThreshold = $threshold;
    }

    /**
     * Analyze query patterns and provide optimization suggestions.
     */
    public function analyzeQueries(): array
    {
        $analysis = [
            'suggestions' => [],
            'patterns' => [],
            'performance_issues' => [],
        ];

        foreach ($this->queries as $query) {
            // Analyze for missing indexes
            if ($this->needsIndex($query['sql'])) {
                $analysis['suggestions'][] = [
                    'type' => 'missing_index',
                    'query' => $query['sql'],
                    'suggestion' => 'Consider adding database index for better performance',
                ];
            }

            // Analyze for SELECT * queries
            if ($this->hasSelectAll($query['sql'])) {
                $analysis['suggestions'][] = [
                    'type' => 'select_all',
                    'query' => $query['sql'],
                    'suggestion' => 'Avoid SELECT * queries, specify only needed columns',
                ];
            }

            // Analyze for missing LIMIT clauses
            if ($this->needsLimit($query['sql'])) {
                $analysis['suggestions'][] = [
                    'type' => 'missing_limit',
                    'query' => $query['sql'],
                    'suggestion' => 'Consider adding LIMIT clause to prevent large result sets',
                ];
            }
        }

        return $analysis;
    }

    /**
     * Check if query might benefit from an index.
     */
    private function needsIndex(string $sql): bool
    {
        return preg_match('/where.*(?!id\s*=)(\w+)\s*=\s*\?/i', $sql) === 1;
    }

    /**
     * Check if query uses SELECT *.
     */
    private function hasSelectAll(string $sql): bool
    {
        return preg_match('/select\s+\*/i', $sql) === 1;
    }

    /**
     * Check if query might need a LIMIT clause.
     */
    private function needsLimit(string $sql): bool
    {
        return preg_match('/select.*from.*where/i', $sql) === 1 &&
               preg_match('/limit\s+\d+/i', $sql) === 0;
    }

    /**
     * Generate performance report.
     */
    public function generatePerformanceReport(): array
    {
        $stats = $this->getQueryStats();
        $analysis = $this->analyzeQueries();

        return [
            'summary' => $stats,
            'slow_queries' => $this->getSlowQueries(),
            'optimization_suggestions' => $analysis['suggestions'],
            'generated_at' => now()->toISOString(),
        ];
    }
}
