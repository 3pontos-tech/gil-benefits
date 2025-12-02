<?php

declare(strict_types=1);

namespace App\Services\Monitoring;

use App\Services\Logging\StructuredLogger;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class MemoryOptimizationService
{
    private const CACHE_PREFIX = 'memory_optimization:';
    private const MEMORY_WARNING_THRESHOLD = 80; // 80%
    private const MEMORY_CRITICAL_THRESHOLD = 90; // 90%
    private const MEMORY_LEAK_DETECTION_THRESHOLD = 20; // 20% increase over time

    public function __construct(
        private readonly StructuredLogger $logger,
        private readonly SystemMonitor $systemMonitor,
        private readonly AlertManager $alertManager
    ) {}

    /**
     * Analyze current memory usage and provide optimization recommendations.
     */
    public function analyzeMemoryUsage(): array
    {
        $currentMemory = $this->getCurrentMemoryMetrics();
        $historicalData = $this->getHistoricalMemoryData(24);
        $trends = $this->analyzeMemoryTrends($historicalData);
        
        $analysis = [
            'timestamp' => now()->toISOString(),
            'current_metrics' => $currentMemory,
            'trend_analysis' => $trends,
            'recommendations' => $this->generateRecommendations($currentMemory, $trends),
            'optimization_opportunities' => $this->identifyOptimizationOpportunities($currentMemory, $historicalData),
            'memory_leaks' => $this->detectPotentialMemoryLeaks($historicalData),
        ];

        // Check for alerts
        $this->checkMemoryAlerts($currentMemory, $trends);

        // Log analysis
        $this->logger->logSystemEvent('memory_analysis_completed', [
            'usage_percentage' => $currentMemory['usage_percentage'],
            'trend' => $trends['trend'],
            'recommendations_count' => count($analysis['recommendations']),
        ]);

        return $analysis;
    }

    /**
     * Get current memory metrics.
     */
    private function getCurrentMemoryMetrics(): array
    {
        $memoryUsage = memory_get_usage(true);
        $memoryPeak = memory_get_peak_usage(true);
        $memoryLimit = $this->parseMemoryLimit(ini_get('memory_limit'));

        return [
            'current_usage_bytes' => $memoryUsage,
            'current_usage_mb' => round($memoryUsage / 1024 / 1024, 2),
            'peak_usage_bytes' => $memoryPeak,
            'peak_usage_mb' => round($memoryPeak / 1024 / 1024, 2),
            'limit_bytes' => $memoryLimit,
            'limit_mb' => round($memoryLimit / 1024 / 1024, 2),
            'usage_percentage' => round(($memoryUsage / $memoryLimit) * 100, 2),
            'peak_percentage' => round(($memoryPeak / $memoryLimit) * 100, 2),
            'available_mb' => round(($memoryLimit - $memoryUsage) / 1024 / 1024, 2),
            'php_version' => PHP_VERSION,
            'opcache_enabled' => function_exists('opcache_get_status') && opcache_get_status() !== false,
        ];
    }

    /**
     * Get historical memory data.
     */
    private function getHistoricalMemoryData(int $hours): array
    {
        $data = [];
        $endTime = now();

        for ($i = 0; $i < $hours; $i++) {
            $time = $endTime->copy()->subHours($i);
            $key = 'performance_metrics:history:' . $time->format('Y-m-d-H');
            $metrics = Cache::get($key, []);

            if (!empty($metrics)) {
                foreach ($metrics as $metric) {
                    if (isset($metric['memory'])) {
                        $data[] = [
                            'timestamp' => $metric['timestamp'],
                            'usage_mb' => $metric['memory']['current_usage_mb'],
                            'usage_percentage' => $metric['memory']['usage_percentage'],
                            'peak_mb' => $metric['memory']['peak_usage_mb'],
                        ];
                    }
                }
            }
        }

        // Sort by timestamp
        usort($data, function ($a, $b) {
            return strtotime($a['timestamp']) <=> strtotime($b['timestamp']);
        });

        return $data;
    }

    /**
     * Analyze memory usage trends.
     */
    private function analyzeMemoryTrends(array $historicalData): array
    {
        if (count($historicalData) < 10) {
            return [
                'trend' => 'insufficient_data',
                'change_percentage' => 0,
                'stability' => 'unknown',
                'prediction' => 'Unable to predict due to insufficient data',
            ];
        }

        // Get recent and older data for comparison
        $recent = array_slice($historicalData, -6); // Last 6 data points
        $older = array_slice($historicalData, -12, 6); // 6 data points before that

        if (empty($older)) {
            return [
                'trend' => 'insufficient_data',
                'change_percentage' => 0,
                'stability' => 'unknown',
                'prediction' => 'Unable to predict due to insufficient data',
            ];
        }

        // Calculate averages
        $recentAvg = array_sum(array_column($recent, 'usage_percentage')) / count($recent);
        $olderAvg = array_sum(array_column($older, 'usage_percentage')) / count($older);

        // Calculate change percentage
        $changePercentage = (($recentAvg - $olderAvg) / $olderAvg) * 100;

        // Determine trend
        $trend = 'stable';
        if ($changePercentage > self::MEMORY_LEAK_DETECTION_THRESHOLD) {
            $trend = 'increasing';
        } elseif ($changePercentage < -self::MEMORY_LEAK_DETECTION_THRESHOLD) {
            $trend = 'decreasing';
        }

        // Calculate stability (coefficient of variation)
        $allUsages = array_column($historicalData, 'usage_percentage');
        $mean = array_sum($allUsages) / count($allUsages);
        $variance = array_sum(array_map(function ($x) use ($mean) {
            return pow($x - $mean, 2);
        }, $allUsages)) / count($allUsages);
        $stdDev = sqrt($variance);
        $coefficientOfVariation = $mean > 0 ? ($stdDev / $mean) * 100 : 0;

        $stability = 'stable';
        if ($coefficientOfVariation > 20) {
            $stability = 'volatile';
        } elseif ($coefficientOfVariation > 10) {
            $stability = 'moderate';
        }

        // Generate prediction
        $prediction = $this->generateMemoryPrediction($trend, $recentAvg, $changePercentage);

        return [
            'trend' => $trend,
            'change_percentage' => round($changePercentage, 2),
            'recent_average' => round($recentAvg, 2),
            'older_average' => round($olderAvg, 2),
            'stability' => $stability,
            'coefficient_of_variation' => round($coefficientOfVariation, 2),
            'prediction' => $prediction,
        ];
    }

    /**
     * Generate memory usage prediction.
     */
    private function generateMemoryPrediction(string $trend, float $currentAvg, float $changePercentage): string
    {
        return match ($trend) {
            'increasing' => $currentAvg > self::MEMORY_WARNING_THRESHOLD 
                ? 'Memory usage may reach critical levels within hours if trend continues'
                : 'Memory usage is trending upward, monitor closely',
            'decreasing' => 'Memory usage is improving, optimization efforts may be working',
            default => $currentAvg > self::MEMORY_WARNING_THRESHOLD
                ? 'Memory usage is stable but elevated, consider optimization'
                : 'Memory usage is stable and within acceptable limits',
        };
    }

    /**
     * Generate optimization recommendations.
     */
    private function generateRecommendations(array $currentMemory, array $trends): array
    {
        $recommendations = [];
        $usage = $currentMemory['usage_percentage'];

        // Critical memory usage
        if ($usage > self::MEMORY_CRITICAL_THRESHOLD) {
            $recommendations[] = [
                'priority' => 'critical',
                'category' => 'immediate_action',
                'title' => 'Critical Memory Usage',
                'description' => "Memory usage is at {$usage}%, immediate action required",
                'actions' => [
                    'Restart application processes if possible',
                    'Clear application caches',
                    'Investigate memory-intensive operations',
                    'Consider increasing memory limit temporarily',
                ],
                'impact' => 'high',
            ];
        }

        // High memory usage
        elseif ($usage > self::MEMORY_WARNING_THRESHOLD) {
            $recommendations[] = [
                'priority' => 'high',
                'category' => 'optimization',
                'title' => 'High Memory Usage',
                'description' => "Memory usage is at {$usage}%, optimization recommended",
                'actions' => [
                    'Review and optimize memory-intensive code',
                    'Implement or improve caching strategies',
                    'Consider pagination for large datasets',
                    'Review database query efficiency',
                ],
                'impact' => 'medium',
            ];
        }

        // Memory trend analysis
        if ($trends['trend'] === 'increasing' && $trends['change_percentage'] > 15) {
            $recommendations[] = [
                'priority' => 'medium',
                'category' => 'trend_analysis',
                'title' => 'Memory Usage Trending Up',
                'description' => "Memory usage has increased by {$trends['change_percentage']}% recently",
                'actions' => [
                    'Investigate potential memory leaks',
                    'Review recent code changes',
                    'Monitor garbage collection efficiency',
                    'Check for unclosed resources',
                ],
                'impact' => 'medium',
            ];
        }

        // Stability issues
        if ($trends['stability'] === 'volatile') {
            $recommendations[] = [
                'priority' => 'medium',
                'category' => 'stability',
                'title' => 'Volatile Memory Usage',
                'description' => 'Memory usage shows high variability',
                'actions' => [
                    'Investigate memory usage spikes',
                    'Review batch processing operations',
                    'Consider memory pooling strategies',
                    'Optimize garbage collection settings',
                ],
                'impact' => 'medium',
            ];
        }

        // OPcache recommendations
        if (!$currentMemory['opcache_enabled']) {
            $recommendations[] = [
                'priority' => 'low',
                'category' => 'performance',
                'title' => 'OPcache Not Enabled',
                'description' => 'PHP OPcache is not enabled, missing performance optimization',
                'actions' => [
                    'Enable PHP OPcache in production',
                    'Configure appropriate OPcache settings',
                    'Monitor OPcache hit rates',
                ],
                'impact' => 'low',
            ];
        }

        // General optimization recommendations
        if (empty($recommendations)) {
            $recommendations[] = [
                'priority' => 'info',
                'category' => 'maintenance',
                'title' => 'Memory Usage Normal',
                'description' => 'Memory usage is within acceptable limits',
                'actions' => [
                    'Continue regular monitoring',
                    'Maintain current optimization practices',
                    'Review memory usage during peak loads',
                ],
                'impact' => 'low',
            ];
        }

        return $recommendations;
    }

    /**
     * Identify optimization opportunities.
     */
    private function identifyOptimizationOpportunities(array $currentMemory, array $historicalData): array
    {
        $opportunities = [];

        // Check for memory limit optimization
        if ($currentMemory['usage_percentage'] < 50) {
            $opportunities[] = [
                'type' => 'memory_limit',
                'title' => 'Memory Limit Optimization',
                'description' => 'Current memory usage is low, memory limit could potentially be reduced',
                'potential_savings' => round($currentMemory['limit_mb'] - ($currentMemory['current_usage_mb'] * 1.5), 2) . ' MB',
                'risk' => 'low',
            ];
        }

        // Check for peak usage optimization
        $peakDifference = $currentMemory['peak_usage_mb'] - $currentMemory['current_usage_mb'];
        if ($peakDifference > 100) { // More than 100MB difference
            $opportunities[] = [
                'type' => 'peak_optimization',
                'title' => 'Peak Memory Usage Optimization',
                'description' => 'Large difference between current and peak memory usage detected',
                'potential_savings' => round($peakDifference, 2) . ' MB',
                'risk' => 'medium',
            ];
        }

        // Check for consistent high usage
        if (!empty($historicalData)) {
            $avgUsage = array_sum(array_column($historicalData, 'usage_percentage')) / count($historicalData);
            if ($avgUsage > 70) {
                $opportunities[] = [
                    'type' => 'consistent_optimization',
                    'title' => 'Consistent High Memory Usage',
                    'description' => 'Memory usage consistently high, systematic optimization needed',
                    'average_usage' => round($avgUsage, 2) . '%',
                    'risk' => 'high',
                ];
            }
        }

        return $opportunities;
    }

    /**
     * Detect potential memory leaks.
     */
    private function detectPotentialMemoryLeaks(array $historicalData): array
    {
        if (count($historicalData) < 20) {
            return [
                'status' => 'insufficient_data',
                'leaks_detected' => false,
                'analysis' => 'Not enough data to detect memory leaks',
            ];
        }

        // Check for consistent upward trend
        $dataPoints = array_column($historicalData, 'usage_percentage');
        $chunks = array_chunk($dataPoints, 5); // Group into 5-point chunks
        
        $increasingChunks = 0;
        $totalChunks = count($chunks);
        
        foreach ($chunks as $chunk) {
            if (count($chunk) >= 2) {
                $first = reset($chunk);
                $last = end($chunk);
                if ($last > $first) {
                    $increasingChunks++;
                }
            }
        }
        
        $increasingPercentage = ($increasingChunks / $totalChunks) * 100;
        
        // Check for memory that never decreases significantly
        $maxUsage = max($dataPoints);
        $minUsage = min($dataPoints);
        $usageRange = $maxUsage - $minUsage;
        
        $leaksDetected = false;
        $analysis = 'No memory leaks detected';
        $severity = 'none';
        
        if ($increasingPercentage > 70 && $usageRange < 10) {
            $leaksDetected = true;
            $severity = 'high';
            $analysis = 'Strong indication of memory leak: consistent upward trend with minimal variation';
        } elseif ($increasingPercentage > 60) {
            $leaksDetected = true;
            $severity = 'medium';
            $analysis = 'Possible memory leak: frequent increases in memory usage';
        } elseif ($usageRange < 5 && $maxUsage > 80) {
            $leaksDetected = true;
            $severity = 'low';
            $analysis = 'Potential memory leak: consistently high usage with little variation';
        }
        
        return [
            'status' => 'analyzed',
            'leaks_detected' => $leaksDetected,
            'severity' => $severity,
            'analysis' => $analysis,
            'increasing_trend_percentage' => round($increasingPercentage, 2),
            'usage_range' => round($usageRange, 2),
            'max_usage' => round($maxUsage, 2),
            'min_usage' => round($minUsage, 2),
        ];
    }

    /**
     * Check for memory alerts.
     */
    private function checkMemoryAlerts(array $currentMemory, array $trends): void
    {
        $usage = $currentMemory['usage_percentage'];

        // Critical memory alert
        if ($usage > self::MEMORY_CRITICAL_THRESHOLD) {
            $this->alertManager->sendAlert(
                'Critical Memory Usage Alert',
                [
                    'usage_percentage' => $usage,
                    'usage_mb' => $currentMemory['current_usage_mb'],
                    'limit_mb' => $currentMemory['limit_mb'],
                    'available_mb' => $currentMemory['available_mb'],
                ],
                'critical'
            );
        }

        // Warning memory alert
        elseif ($usage > self::MEMORY_WARNING_THRESHOLD) {
            $this->alertManager->sendAlert(
                'High Memory Usage Warning',
                [
                    'usage_percentage' => $usage,
                    'usage_mb' => $currentMemory['current_usage_mb'],
                    'trend' => $trends['trend'],
                    'change_percentage' => $trends['change_percentage'],
                ],
                'warning'
            );
        }

        // Memory leak alert
        if ($trends['trend'] === 'increasing' && $trends['change_percentage'] > 25) {
            $this->alertManager->sendAlert(
                'Potential Memory Leak Detected',
                [
                    'change_percentage' => $trends['change_percentage'],
                    'current_usage' => $usage,
                    'trend_analysis' => $trends,
                ],
                'error'
            );
        }
    }

    /**
     * Perform memory optimization actions.
     */
    public function performOptimization(): array
    {
        $actions = [];
        $beforeMemory = $this->getCurrentMemoryMetrics();

        // Clear various caches
        $actions[] = $this->clearApplicationCaches();
        $actions[] = $this->clearOpcache();
        $actions[] = $this->runGarbageCollection();

        // Get memory after optimization
        $afterMemory = $this->getCurrentMemoryMetrics();
        
        $memoryFreed = $beforeMemory['current_usage_mb'] - $afterMemory['current_usage_mb'];

        $result = [
            'timestamp' => now()->toISOString(),
            'before_optimization' => $beforeMemory,
            'after_optimization' => $afterMemory,
            'memory_freed_mb' => round($memoryFreed, 2),
            'actions_performed' => $actions,
            'success' => $memoryFreed > 0,
        ];

        $this->logger->logSystemEvent('memory_optimization_performed', [
            'memory_freed_mb' => $memoryFreed,
            'actions_count' => count($actions),
            'success' => $result['success'],
        ]);

        return $result;
    }

    /**
     * Clear application caches.
     */
    private function clearApplicationCaches(): array
    {
        try {
            Cache::flush();
            return [
                'action' => 'clear_application_cache',
                'status' => 'success',
                'description' => 'Application cache cleared successfully',
            ];
        } catch (\Exception $e) {
            return [
                'action' => 'clear_application_cache',
                'status' => 'failed',
                'description' => 'Failed to clear application cache: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Clear OPcache.
     */
    private function clearOpcache(): array
    {
        try {
            if (function_exists('opcache_reset')) {
                opcache_reset();
                return [
                    'action' => 'clear_opcache',
                    'status' => 'success',
                    'description' => 'OPcache cleared successfully',
                ];
            } else {
                return [
                    'action' => 'clear_opcache',
                    'status' => 'skipped',
                    'description' => 'OPcache not available',
                ];
            }
        } catch (\Exception $e) {
            return [
                'action' => 'clear_opcache',
                'status' => 'failed',
                'description' => 'Failed to clear OPcache: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Run garbage collection.
     */
    private function runGarbageCollection(): array
    {
        try {
            $cycles = gc_collect_cycles();
            return [
                'action' => 'garbage_collection',
                'status' => 'success',
                'description' => "Garbage collection completed, {$cycles} cycles collected",
                'cycles_collected' => $cycles,
            ];
        } catch (\Exception $e) {
            return [
                'action' => 'garbage_collection',
                'status' => 'failed',
                'description' => 'Failed to run garbage collection: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Parse memory limit string to bytes.
     */
    private function parseMemoryLimit(string $limit): int
    {
        $limit = trim($limit);
        $last = strtolower($limit[strlen($limit) - 1]);
        $value = (int) $limit;

        return match ($last) {
            'g' => $value * 1024 * 1024 * 1024,
            'm' => $value * 1024 * 1024,
            'k' => $value * 1024,
            default => $value,
        };
    }
}