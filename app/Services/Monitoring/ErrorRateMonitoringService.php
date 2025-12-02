<?php

declare(strict_types=1);

namespace App\Services\Monitoring;

use App\Services\Logging\StructuredLogger;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ErrorRateMonitoringService
{
    private const CACHE_PREFIX = 'error_rate_monitoring:';
    private const ERROR_RATE_WARNING_THRESHOLD = 2.0; // 2%
    private const ERROR_RATE_CRITICAL_THRESHOLD = 5.0; // 5%
    private const SPIKE_DETECTION_THRESHOLD = 300; // 300% increase

    public function __construct(
        private readonly StructuredLogger $logger,
        private readonly SystemMonitor $systemMonitor,
        private readonly AlertManager $alertManager
    ) {}

    /**
     * Record an error occurrence.
     */
    public function recordError(
        string $errorType,
        string $message,
        array $context = [],
        string $severity = 'error',
        ?string $userId = null,
        ?string $sessionId = null
    ): void {
        $errorData = [
            'error_type' => $errorType,
            'message' => $message,
            'severity' => $severity,
            'context' => $context,
            'user_id' => $userId,
            'session_id' => $sessionId,
            'timestamp' => now()->toISOString(),
            'hour' => now()->format('Y-m-d-H'),
            'date' => now()->format('Y-m-d'),
        ];

        // Store error data
        $this->storeErrorData($errorData);

        // Update error counters
        $this->updateErrorCounters($errorData);

        // Check for error rate alerts
        $this->checkErrorRateAlerts($errorType, $severity);

        // Log structured error
        $this->logger->logSystemEvent('error_recorded', [
            'error_type' => $errorType,
            'severity' => $severity,
            'user_id' => $userId,
        ], $severity);
    }

    /**
     * Record a successful request.
     */
    public function recordRequest(
        string $endpoint = null,
        int $statusCode = 200,
        float $responseTime = null,
        ?string $userId = null
    ): void {
        $requestData = [
            'endpoint' => $endpoint,
            'status_code' => $statusCode,
            'response_time' => $responseTime,
            'user_id' => $userId,
            'timestamp' => now()->toISOString(),
            'hour' => now()->format('Y-m-d-H'),
            'date' => now()->format('Y-m-d'),
            'is_error' => $statusCode >= 400,
        ];

        // Update request counters
        $this->updateRequestCounters($requestData);

        // Record error if status code indicates error
        if ($statusCode >= 400) {
            $this->recordError(
                'http_error',
                "HTTP {$statusCode} error on {$endpoint}",
                ['status_code' => $statusCode, 'endpoint' => $endpoint],
                $statusCode >= 500 ? 'error' : 'warning',
                $userId
            );
        }
    }

    /**
     * Get current error rate statistics.
     */
    public function getErrorRateStatistics(int $hours = 24): array
    {
        $statistics = [
            'period' => [
                'hours' => $hours,
                'start_time' => now()->subHours($hours)->toISOString(),
                'end_time' => now()->toISOString(),
            ],
            'overall' => $this->getOverallErrorRate($hours),
            'by_hour' => $this->getHourlyErrorRates($hours),
            'by_type' => $this->getErrorRatesByType($hours),
            'by_severity' => $this->getErrorRatesBySeverity($hours),
            'trends' => $this->analyzeErrorRateTrends($hours),
            'top_errors' => $this->getTopErrors($hours),
        ];

        return $statistics;
    }

    /**
     * Analyze error rate trends and patterns.
     */
    public function analyzeErrorPatterns(int $hours = 24): array
    {
        $patterns = [
            'timestamp' => now()->toISOString(),
            'analysis_period' => $hours,
            'error_spikes' => $this->detectErrorSpikes($hours),
            'recurring_errors' => $this->identifyRecurringErrors($hours),
            'user_impact' => $this->analyzeUserImpact($hours),
            'endpoint_analysis' => $this->analyzeEndpointErrors($hours),
            'time_patterns' => $this->analyzeTimePatterns($hours),
            'recommendations' => $this->generateErrorRecommendations($hours),
        ];

        return $patterns;
    }

    /**
     * Store error data for analysis.
     */
    private function storeErrorData(array $errorData): void
    {
        // Store in hourly bucket
        $hourlyKey = self::CACHE_PREFIX . 'errors:' . $errorData['hour'];
        $hourlyErrors = Cache::get($hourlyKey, []);
        $hourlyErrors[] = $errorData;

        // Keep only last 1000 errors per hour
        if (count($hourlyErrors) > 1000) {
            $hourlyErrors = array_slice($hourlyErrors, -1000);
        }

        Cache::put($hourlyKey, $hourlyErrors, 3600 * 48); // Store for 48 hours

        // Store in daily summary
        $dailyKey = self::CACHE_PREFIX . 'daily_summary:' . $errorData['date'];
        $dailySummary = Cache::get($dailyKey, [
            'date' => $errorData['date'],
            'total_errors' => 0,
            'by_type' => [],
            'by_severity' => [],
            'unique_users' => [],
        ]);

        $dailySummary['total_errors']++;
        $dailySummary['by_type'][$errorData['error_type']] = 
            ($dailySummary['by_type'][$errorData['error_type']] ?? 0) + 1;
        $dailySummary['by_severity'][$errorData['severity']] = 
            ($dailySummary['by_severity'][$errorData['severity']] ?? 0) + 1;

        if ($errorData['user_id']) {
            $dailySummary['unique_users'][$errorData['user_id']] = true;
        }

        Cache::put($dailyKey, $dailySummary, 3600 * 48);
    }

    /**
     * Update error counters.
     */
    private function updateErrorCounters(array $errorData): void
    {
        $hour = $errorData['hour'];

        // Update hourly error counter
        $errorCountKey = self::CACHE_PREFIX . 'count:errors:' . $hour;
        $currentCount = Cache::get($errorCountKey, 0);
        Cache::put($errorCountKey, $currentCount + 1, 3600 * 48);

        // Update error type counter
        $typeCountKey = self::CACHE_PREFIX . 'count:type:' . $errorData['error_type'] . ':' . $hour;
        $typeCount = Cache::get($typeCountKey, 0);
        Cache::put($typeCountKey, $typeCount + 1, 3600 * 48);

        // Update severity counter
        $severityCountKey = self::CACHE_PREFIX . 'count:severity:' . $errorData['severity'] . ':' . $hour;
        $severityCount = Cache::get($severityCountKey, 0);
        Cache::put($severityCountKey, $severityCount + 1, 3600 * 48);
    }

    /**
     * Update request counters.
     */
    private function updateRequestCounters(array $requestData): void
    {
        $hour = $requestData['hour'];

        // Update hourly request counter
        $requestCountKey = self::CACHE_PREFIX . 'count:requests:' . $hour;
        $currentCount = Cache::get($requestCountKey, 0);
        Cache::put($requestCountKey, $currentCount + 1, 3600 * 48);

        // Update endpoint counter if provided
        if ($requestData['endpoint']) {
            $endpointKey = self::CACHE_PREFIX . 'count:endpoint:' . md5($requestData['endpoint']) . ':' . $hour;
            $endpointCount = Cache::get($endpointKey, 0);
            Cache::put($endpointKey, $endpointCount + 1, 3600 * 48);

            // Store endpoint mapping
            $endpointMapKey = self::CACHE_PREFIX . 'endpoint_map:' . md5($requestData['endpoint']);
            Cache::put($endpointMapKey, $requestData['endpoint'], 3600 * 48);
        }
    }

    /**
     * Get overall error rate for the specified period.
     */
    private function getOverallErrorRate(int $hours): array
    {
        $totalErrors = 0;
        $totalRequests = 0;

        for ($i = 0; $i < $hours; $i++) {
            $hour = now()->subHours($i)->format('Y-m-d-H');
            $errorCountKey = self::CACHE_PREFIX . 'count:errors:' . $hour;
            $requestCountKey = self::CACHE_PREFIX . 'count:requests:' . $hour;

            $totalErrors += Cache::get($errorCountKey, 0);
            $totalRequests += Cache::get($requestCountKey, 0);
        }

        $errorRate = $totalRequests > 0 ? ($totalErrors / $totalRequests) * 100 : 0;

        return [
            'total_errors' => $totalErrors,
            'total_requests' => $totalRequests,
            'error_rate_percentage' => round($errorRate, 3),
            'status' => $this->getErrorRateStatus($errorRate),
        ];
    }

    /**
     * Get hourly error rates.
     */
    private function getHourlyErrorRates(int $hours): array
    {
        $hourlyRates = [];

        for ($i = $hours - 1; $i >= 0; $i--) {
            $hour = now()->subHours($i)->format('Y-m-d-H');
            $errorCountKey = self::CACHE_PREFIX . 'count:errors:' . $hour;
            $requestCountKey = self::CACHE_PREFIX . 'count:requests:' . $hour;

            $errors = Cache::get($errorCountKey, 0);
            $requests = Cache::get($requestCountKey, 0);
            $errorRate = $requests > 0 ? ($errors / $requests) * 100 : 0;

            $hourlyRates[] = [
                'hour' => $hour,
                'errors' => $errors,
                'requests' => $requests,
                'error_rate_percentage' => round($errorRate, 3),
                'status' => $this->getErrorRateStatus($errorRate),
            ];
        }

        return $hourlyRates;
    }

    /**
     * Get error rates by type.
     */
    private function getErrorRatesByType(int $hours): array
    {
        $errorTypes = [];

        for ($i = 0; $i < $hours; $i++) {
            $hour = now()->subHours($i)->format('Y-m-d-H');
            $hourlyKey = self::CACHE_PREFIX . 'errors:' . $hour;
            $hourlyErrors = Cache::get($hourlyKey, []);

            foreach ($hourlyErrors as $error) {
                $type = $error['error_type'];
                if (!isset($errorTypes[$type])) {
                    $errorTypes[$type] = 0;
                }
                $errorTypes[$type]++;
            }
        }

        // Sort by frequency
        arsort($errorTypes);

        return array_map(function ($count, $type) use ($hours) {
            $totalRequests = $this->getTotalRequests($hours);
            $errorRate = $totalRequests > 0 ? ($count / $totalRequests) * 100 : 0;

            return [
                'error_type' => $type,
                'count' => $count,
                'error_rate_percentage' => round($errorRate, 3),
            ];
        }, $errorTypes, array_keys($errorTypes));
    }

    /**
     * Get error rates by severity.
     */
    private function getErrorRatesBySeverity(int $hours): array
    {
        $severities = ['critical' => 0, 'error' => 0, 'warning' => 0, 'info' => 0];

        for ($i = 0; $i < $hours; $i++) {
            $hour = now()->subHours($i)->format('Y-m-d-H');
            $hourlyKey = self::CACHE_PREFIX . 'errors:' . $hour;
            $hourlyErrors = Cache::get($hourlyKey, []);

            foreach ($hourlyErrors as $error) {
                $severity = $error['severity'] ?? 'error';
                if (isset($severities[$severity])) {
                    $severities[$severity]++;
                }
            }
        }

        $totalRequests = $this->getTotalRequests($hours);

        return array_map(function ($count, $severity) use ($totalRequests) {
            $errorRate = $totalRequests > 0 ? ($count / $totalRequests) * 100 : 0;

            return [
                'severity' => $severity,
                'count' => $count,
                'error_rate_percentage' => round($errorRate, 3),
            ];
        }, $severities, array_keys($severities));
    }

    /**
     * Analyze error rate trends.
     */
    private function analyzeErrorRateTrends(int $hours): array
    {
        $hourlyRates = $this->getHourlyErrorRates($hours);

        if (count($hourlyRates) < 2) {
            return [
                'trend' => 'insufficient_data',
                'change_percentage' => 0,
                'prediction' => 'Unable to predict due to insufficient data',
            ];
        }

        // Get recent and older data for comparison
        $recent = array_slice($hourlyRates, -6); // Last 6 hours
        $older = array_slice($hourlyRates, -12, 6); // 6 hours before that

        if (empty($older)) {
            return [
                'trend' => 'insufficient_data',
                'change_percentage' => 0,
                'prediction' => 'Unable to predict due to insufficient data',
            ];
        }

        $recentAvg = array_sum(array_column($recent, 'error_rate_percentage')) / count($recent);
        $olderAvg = array_sum(array_column($older, 'error_rate_percentage')) / count($older);

        $changePercentage = $olderAvg > 0 ? (($recentAvg - $olderAvg) / $olderAvg) * 100 : 0;

        $trend = 'stable';
        if ($changePercentage > 50) {
            $trend = 'increasing';
        } elseif ($changePercentage < -50) {
            $trend = 'decreasing';
        }

        $prediction = match ($trend) {
            'increasing' => 'Error rate is trending upward, investigate potential issues',
            'decreasing' => 'Error rate is improving, recent fixes may be working',
            default => 'Error rate is stable',
        };

        return [
            'trend' => $trend,
            'change_percentage' => round($changePercentage, 2),
            'recent_average' => round($recentAvg, 3),
            'older_average' => round($olderAvg, 3),
            'prediction' => $prediction,
        ];
    }

    /**
     * Get top errors by frequency.
     */
    private function getTopErrors(int $hours, int $limit = 10): array
    {
        $errorCounts = [];

        for ($i = 0; $i < $hours; $i++) {
            $hour = now()->subHours($i)->format('Y-m-d-H');
            $hourlyKey = self::CACHE_PREFIX . 'errors:' . $hour;
            $hourlyErrors = Cache::get($hourlyKey, []);

            foreach ($hourlyErrors as $error) {
                $key = $error['error_type'] . '|' . $error['message'];
                if (!isset($errorCounts[$key])) {
                    $errorCounts[$key] = [
                        'error_type' => $error['error_type'],
                        'message' => $error['message'],
                        'count' => 0,
                        'first_seen' => $error['timestamp'],
                        'last_seen' => $error['timestamp'],
                        'severities' => [],
                    ];
                }

                $errorCounts[$key]['count']++;
                $errorCounts[$key]['last_seen'] = $error['timestamp'];
                $errorCounts[$key]['severities'][$error['severity']] = 
                    ($errorCounts[$key]['severities'][$error['severity']] ?? 0) + 1;
            }
        }

        // Sort by count and return top errors
        uasort($errorCounts, function ($a, $b) {
            return $b['count'] <=> $a['count'];
        });

        return array_slice(array_values($errorCounts), 0, $limit);
    }

    /**
     * Detect error spikes.
     */
    private function detectErrorSpikes(int $hours): array
    {
        $hourlyRates = $this->getHourlyErrorRates($hours);
        $spikes = [];

        for ($i = 1; $i < count($hourlyRates); $i++) {
            $current = $hourlyRates[$i]['error_rate_percentage'];
            $previous = $hourlyRates[$i - 1]['error_rate_percentage'];

            if ($previous > 0) {
                $increasePercentage = (($current - $previous) / $previous) * 100;

                if ($increasePercentage > self::SPIKE_DETECTION_THRESHOLD) {
                    $spikes[] = [
                        'hour' => $hourlyRates[$i]['hour'],
                        'error_rate' => $current,
                        'previous_rate' => $previous,
                        'increase_percentage' => round($increasePercentage, 2),
                        'severity' => $increasePercentage > 1000 ? 'critical' : 'high',
                    ];
                }
            }
        }

        return $spikes;
    }

    /**
     * Identify recurring errors.
     */
    private function identifyRecurringErrors(int $hours): array
    {
        $topErrors = $this->getTopErrors($hours, 20);
        $recurringErrors = [];

        foreach ($topErrors as $error) {
            if ($error['count'] >= 5) { // Errors that occurred 5+ times
                $recurringErrors[] = [
                    'error_type' => $error['error_type'],
                    'message' => substr($error['message'], 0, 100) . '...',
                    'count' => $error['count'],
                    'frequency_per_hour' => round($error['count'] / $hours, 2),
                    'first_seen' => $error['first_seen'],
                    'last_seen' => $error['last_seen'],
                    'primary_severity' => array_keys($error['severities'], max($error['severities']))[0],
                ];
            }
        }

        return $recurringErrors;
    }

    /**
     * Analyze user impact.
     */
    private function analyzeUserImpact(int $hours): array
    {
        $affectedUsers = [];
        $totalUsers = 0;

        for ($i = 0; $i < $hours; $i++) {
            $hour = now()->subHours($i)->format('Y-m-d-H');
            $hourlyKey = self::CACHE_PREFIX . 'errors:' . $hour;
            $hourlyErrors = Cache::get($hourlyKey, []);

            foreach ($hourlyErrors as $error) {
                if ($error['user_id']) {
                    $affectedUsers[$error['user_id']] = true;
                }
            }

            // Count total users (this would need to be tracked separately)
            $userActivityKey = "user_activity:hourly:{$hour}";
            $activities = Cache::get($userActivityKey, []);
            $hourlyUsers = count(array_unique(array_column($activities, 'user_id')));
            $totalUsers = max($totalUsers, $hourlyUsers);
        }

        $affectedUserCount = count($affectedUsers);
        $impactPercentage = $totalUsers > 0 ? ($affectedUserCount / $totalUsers) * 100 : 0;

        return [
            'affected_users' => $affectedUserCount,
            'total_users' => $totalUsers,
            'impact_percentage' => round($impactPercentage, 2),
            'severity' => $this->getUserImpactSeverity($impactPercentage),
        ];
    }

    /**
     * Analyze endpoint errors.
     */
    private function analyzeEndpointErrors(int $hours): array
    {
        // This would require more detailed endpoint tracking
        // For now, return a placeholder
        return [
            'analysis' => 'Endpoint error analysis requires additional tracking implementation',
            'top_error_endpoints' => [],
        ];
    }

    /**
     * Analyze time patterns in errors.
     */
    private function analyzeTimePatterns(int $hours): array
    {
        $hourlyRates = $this->getHourlyErrorRates($hours);
        $hourPatterns = [];

        foreach ($hourlyRates as $rate) {
            $hour = (int) substr($rate['hour'], -2);
            if (!isset($hourPatterns[$hour])) {
                $hourPatterns[$hour] = [];
            }
            $hourPatterns[$hour][] = $rate['error_rate_percentage'];
        }

        // Calculate average error rate for each hour of day
        $avgByHour = [];
        foreach ($hourPatterns as $hour => $rates) {
            $avgByHour[$hour] = array_sum($rates) / count($rates);
        }

        // Find peak error hours
        arsort($avgByHour);
        $peakHours = array_slice(array_keys($avgByHour), 0, 3, true);

        return [
            'peak_error_hours' => $peakHours,
            'hourly_averages' => $avgByHour,
            'pattern_analysis' => $this->analyzeHourlyPattern($avgByHour),
        ];
    }

    /**
     * Generate error-based recommendations.
     */
    private function generateErrorRecommendations(int $hours): array
    {
        $statistics = $this->getErrorRateStatistics($hours);
        $recommendations = [];

        // High error rate recommendations
        if ($statistics['overall']['error_rate_percentage'] > self::ERROR_RATE_CRITICAL_THRESHOLD) {
            $recommendations[] = [
                'priority' => 'critical',
                'title' => 'Critical Error Rate',
                'description' => 'Error rate is critically high at ' . $statistics['overall']['error_rate_percentage'] . '%',
                'actions' => [
                    'Investigate and fix top recurring errors immediately',
                    'Review recent deployments for potential issues',
                    'Consider rolling back recent changes if necessary',
                    'Implement circuit breakers for failing services',
                ],
            ];
        } elseif ($statistics['overall']['error_rate_percentage'] > self::ERROR_RATE_WARNING_THRESHOLD) {
            $recommendations[] = [
                'priority' => 'high',
                'title' => 'Elevated Error Rate',
                'description' => 'Error rate is elevated at ' . $statistics['overall']['error_rate_percentage'] . '%',
                'actions' => [
                    'Review and prioritize fixing top errors',
                    'Enhance error monitoring and alerting',
                    'Implement better error handling in critical paths',
                ],
            ];
        }

        // Trending recommendations
        if ($statistics['trends']['trend'] === 'increasing') {
            $recommendations[] = [
                'priority' => 'medium',
                'title' => 'Increasing Error Trend',
                'description' => 'Error rate is trending upward by ' . $statistics['trends']['change_percentage'] . '%',
                'actions' => [
                    'Investigate root cause of increasing errors',
                    'Review system performance and capacity',
                    'Check for resource exhaustion or degradation',
                ],
            ];
        }

        return $recommendations;
    }

    /**
     * Check for error rate alerts.
     */
    private function checkErrorRateAlerts(string $errorType, string $severity): void
    {
        $currentHour = now()->format('Y-m-d-H');
        $errorCountKey = self::CACHE_PREFIX . 'count:errors:' . $currentHour;
        $requestCountKey = self::CACHE_PREFIX . 'count:requests:' . $currentHour;

        $errors = Cache::get($errorCountKey, 0);
        $requests = Cache::get($requestCountKey, 1);
        $errorRate = ($errors / $requests) * 100;

        // Critical error rate alert
        if ($errorRate > self::ERROR_RATE_CRITICAL_THRESHOLD) {
            $this->alertManager->sendAlert(
                'Critical Error Rate Alert',
                [
                    'error_rate_percentage' => round($errorRate, 3),
                    'errors' => $errors,
                    'requests' => $requests,
                    'threshold' => self::ERROR_RATE_CRITICAL_THRESHOLD,
                    'latest_error_type' => $errorType,
                ],
                'critical'
            );
        }

        // Warning error rate alert
        elseif ($errorRate > self::ERROR_RATE_WARNING_THRESHOLD) {
            $this->alertManager->sendAlert(
                'High Error Rate Warning',
                [
                    'error_rate_percentage' => round($errorRate, 3),
                    'errors' => $errors,
                    'requests' => $requests,
                    'threshold' => self::ERROR_RATE_WARNING_THRESHOLD,
                    'latest_error_type' => $errorType,
                ],
                'warning'
            );
        }

        // Critical error type alert
        if ($severity === 'critical') {
            $this->alertManager->sendAlert(
                'Critical Error Detected',
                [
                    'error_type' => $errorType,
                    'severity' => $severity,
                    'current_error_rate' => round($errorRate, 3),
                ],
                'error'
            );
        }
    }

    /**
     * Get error rate status.
     */
    private function getErrorRateStatus(float $errorRate): string
    {
        if ($errorRate > self::ERROR_RATE_CRITICAL_THRESHOLD) {
            return 'critical';
        } elseif ($errorRate > self::ERROR_RATE_WARNING_THRESHOLD) {
            return 'warning';
        }

        return 'normal';
    }

    /**
     * Get user impact severity.
     */
    private function getUserImpactSeverity(float $impactPercentage): string
    {
        if ($impactPercentage > 50) {
            return 'critical';
        } elseif ($impactPercentage > 20) {
            return 'high';
        } elseif ($impactPercentage > 5) {
            return 'medium';
        }

        return 'low';
    }

    /**
     * Analyze hourly pattern.
     */
    private function analyzeHourlyPattern(array $avgByHour): string
    {
        if (empty($avgByHour)) {
            return 'No pattern data available';
        }

        $maxHour = array_keys($avgByHour, max($avgByHour))[0];
        $minHour = array_keys($avgByHour, min($avgByHour))[0];

        return "Peak errors occur around {$maxHour}:00, lowest around {$minHour}:00";
    }

    /**
     * Get total requests for the period.
     */
    private function getTotalRequests(int $hours): int
    {
        $total = 0;
        for ($i = 0; $i < $hours; $i++) {
            $hour = now()->subHours($i)->format('Y-m-d-H');
            $requestCountKey = self::CACHE_PREFIX . 'count:requests:' . $hour;
            $total += Cache::get($requestCountKey, 0);
        }
        return $total;
    }
}