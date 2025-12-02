<?php

declare(strict_types=1);

namespace App\Services\Monitoring;

use App\Models\Users\User;
use App\Services\Logging\StructuredLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class UserActivityTracker
{
    private const CACHE_PREFIX = 'user_activity:';
    private const ACTIVITY_RETENTION_HOURS = 168; // 1 week

    public function __construct(
        private readonly StructuredLogger $logger
    ) {}

    /**
     * Track user activity.
     */
    public function trackActivity(
        ?User $user,
        string $action,
        array $context = [],
        ?Request $request = null
    ): void {
        $activityData = [
            'user_id' => $user?->id,
            'user_email' => $user?->email,
            'action' => $action,
            'context' => $context,
            'timestamp' => now()->toISOString(),
            'session_id' => session()->getId(),
        ];

        // Add request information if available
        if ($request) {
            $activityData['request'] = [
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'url' => $request->fullUrl(),
                'method' => $request->method(),
                'referer' => $request->header('referer'),
            ];
        }

        // Store activity
        $this->storeActivity($activityData);

        // Update user statistics
        if ($user) {
            $this->updateUserStatistics($user, $action);
        }

        // Update global statistics
        $this->updateGlobalStatistics($action);

        // Log structured activity
        $this->logger->logUserActivity($user, $action, $context);
    }

    /**
     * Track page view.
     */
    public function trackPageView(?User $user, string $page, ?Request $request = null): void
    {
        $this->trackActivity($user, 'page_view', ['page' => $page], $request);
    }

    /**
     * Track user login.
     */
    public function trackLogin(User $user, ?Request $request = null): void
    {
        $this->trackActivity($user, 'login', [
            'login_time' => now()->toISOString(),
        ], $request);

        // Update login statistics
        $this->updateLoginStatistics($user);
    }

    /**
     * Track user logout.
     */
    public function trackLogout(User $user, ?Request $request = null): void
    {
        $sessionDuration = $this->calculateSessionDuration($user);
        
        $this->trackActivity($user, 'logout', [
            'logout_time' => now()->toISOString(),
            'session_duration_minutes' => $sessionDuration,
        ], $request);
    }

    /**
     * Track feature usage.
     */
    public function trackFeatureUsage(?User $user, string $feature, array $details = []): void
    {
        $this->trackActivity($user, 'feature_usage', [
            'feature' => $feature,
            'details' => $details,
        ]);

        // Update feature usage statistics
        $this->updateFeatureStatistics($feature, $user);
    }

    /**
     * Track error occurrence.
     */
    public function trackError(?User $user, string $errorType, string $message, array $context = []): void
    {
        $this->trackActivity($user, 'error', [
            'error_type' => $errorType,
            'message' => $message,
            'context' => $context,
        ]);
    }

    /**
     * Store activity data.
     */
    private function storeActivity(array $activityData): void
    {
        $hourKey = self::CACHE_PREFIX . 'hourly:' . now()->format('Y-m-d-H');
        $dailyKey = self::CACHE_PREFIX . 'daily:' . now()->format('Y-m-d');

        // Store in hourly bucket
        $hourlyActivities = Cache::get($hourKey, []);
        $hourlyActivities[] = $activityData;

        // Keep only last 1000 activities per hour
        if (count($hourlyActivities) > 1000) {
            $hourlyActivities = array_slice($hourlyActivities, -1000);
        }

        Cache::put($hourKey, $hourlyActivities, self::ACTIVITY_RETENTION_HOURS * 3600);

        // Store in daily summary
        $dailyActivities = Cache::get($dailyKey, []);
        $dailyActivities[] = [
            'user_id' => $activityData['user_id'],
            'action' => $activityData['action'],
            'timestamp' => $activityData['timestamp'],
        ];

        Cache::put($dailyKey, $dailyActivities, self::ACTIVITY_RETENTION_HOURS * 3600);
    }

    /**
     * Update user-specific statistics.
     */
    private function updateUserStatistics(User $user, string $action): void
    {
        $userStatsKey = self::CACHE_PREFIX . 'user_stats:' . $user->id . ':' . now()->format('Y-m-d');
        $userStats = Cache::get($userStatsKey, [
            'user_id' => $user->id,
            'date' => now()->format('Y-m-d'),
            'total_actions' => 0,
            'actions' => [],
            'first_activity' => null,
            'last_activity' => null,
        ]);

        $userStats['total_actions']++;
        $userStats['actions'][$action] = ($userStats['actions'][$action] ?? 0) + 1;
        $userStats['last_activity'] = now()->toISOString();

        if (!$userStats['first_activity']) {
            $userStats['first_activity'] = now()->toISOString();
        }

        Cache::put($userStatsKey, $userStats, 86400); // Store for 24 hours
    }

    /**
     * Update global activity statistics.
     */
    private function updateGlobalStatistics(string $action): void
    {
        $globalStatsKey = self::CACHE_PREFIX . 'global_stats:' . now()->format('Y-m-d');
        $globalStats = Cache::get($globalStatsKey, [
            'date' => now()->format('Y-m-d'),
            'total_actions' => 0,
            'actions' => [],
            'unique_users' => [],
            'unique_sessions' => [],
        ]);

        $globalStats['total_actions']++;
        $globalStats['actions'][$action] = ($globalStats['actions'][$action] ?? 0) + 1;

        Cache::put($globalStatsKey, $globalStats, 86400); // Store for 24 hours
    }

    /**
     * Update login statistics.
     */
    private function updateLoginStatistics(User $user): void
    {
        $loginStatsKey = self::CACHE_PREFIX . 'login_stats:' . now()->format('Y-m-d');
        $loginStats = Cache::get($loginStatsKey, [
            'date' => now()->format('Y-m-d'),
            'total_logins' => 0,
            'unique_users' => [],
            'login_times' => [],
        ]);

        $loginStats['total_logins']++;
        $loginStats['unique_users'][$user->id] = $user->email;
        $loginStats['login_times'][] = now()->format('H:i');

        Cache::put($loginStatsKey, $loginStats, 86400);

        // Update user's last login
        $user->update(['last_login_at' => now()]);
    }

    /**
     * Update feature usage statistics.
     */
    private function updateFeatureStatistics(string $feature, ?User $user): void
    {
        $featureStatsKey = self::CACHE_PREFIX . 'feature_stats:' . now()->format('Y-m-d');
        $featureStats = Cache::get($featureStatsKey, []);

        if (!isset($featureStats[$feature])) {
            $featureStats[$feature] = [
                'usage_count' => 0,
                'unique_users' => [],
                'first_used' => now()->toISOString(),
                'last_used' => now()->toISOString(),
            ];
        }

        $featureStats[$feature]['usage_count']++;
        $featureStats[$feature]['last_used'] = now()->toISOString();

        if ($user) {
            $featureStats[$feature]['unique_users'][$user->id] = $user->email;
        }

        Cache::put($featureStatsKey, $featureStats, 86400);
    }

    /**
     * Calculate session duration for a user.
     */
    private function calculateSessionDuration(User $user): int
    {
        $sessionKey = self::CACHE_PREFIX . 'session:' . $user->id . ':' . session()->getId();
        $sessionStart = Cache::get($sessionKey);

        if (!$sessionStart) {
            return 0;
        }

        return now()->diffInMinutes($sessionStart);
    }

    /**
     * Get user activity analytics.
     */
    public function getUserAnalytics(int $days = 7): array
    {
        $analytics = [
            'period' => [
                'days' => $days,
                'start_date' => now()->subDays($days)->format('Y-m-d'),
                'end_date' => now()->format('Y-m-d'),
            ],
            'daily_stats' => [],
            'top_actions' => [],
            'top_features' => [],
            'user_engagement' => [],
        ];

        // Collect daily statistics
        for ($i = 0; $i < $days; $i++) {
            $date = now()->subDays($i)->format('Y-m-d');
            $dailyStats = $this->getDailyStatistics($date);
            $analytics['daily_stats'][$date] = $dailyStats;
        }

        // Calculate aggregated metrics
        $analytics['top_actions'] = $this->getTopActions($days);
        $analytics['top_features'] = $this->getTopFeatures($days);
        $analytics['user_engagement'] = $this->getUserEngagementMetrics($days);

        return $analytics;
    }

    /**
     * Get daily statistics for a specific date.
     */
    private function getDailyStatistics(string $date): array
    {
        $globalStatsKey = self::CACHE_PREFIX . 'global_stats:' . $date;
        $loginStatsKey = self::CACHE_PREFIX . 'login_stats:' . $date;
        $featureStatsKey = self::CACHE_PREFIX . 'feature_stats:' . $date;

        $globalStats = Cache::get($globalStatsKey, []);
        $loginStats = Cache::get($loginStatsKey, []);
        $featureStats = Cache::get($featureStatsKey, []);

        return [
            'total_actions' => $globalStats['total_actions'] ?? 0,
            'total_logins' => $loginStats['total_logins'] ?? 0,
            'unique_users' => count($loginStats['unique_users'] ?? []),
            'features_used' => count($featureStats),
            'actions_breakdown' => $globalStats['actions'] ?? [],
        ];
    }

    /**
     * Get top actions across the specified period.
     */
    private function getTopActions(int $days): array
    {
        $actionCounts = [];

        for ($i = 0; $i < $days; $i++) {
            $date = now()->subDays($i)->format('Y-m-d');
            $globalStatsKey = self::CACHE_PREFIX . 'global_stats:' . $date;
            $globalStats = Cache::get($globalStatsKey, []);

            foreach ($globalStats['actions'] ?? [] as $action => $count) {
                $actionCounts[$action] = ($actionCounts[$action] ?? 0) + $count;
            }
        }

        arsort($actionCounts);
        return array_slice($actionCounts, 0, 10, true);
    }

    /**
     * Get top features across the specified period.
     */
    private function getTopFeatures(int $days): array
    {
        $featureCounts = [];

        for ($i = 0; $i < $days; $i++) {
            $date = now()->subDays($i)->format('Y-m-d');
            $featureStatsKey = self::CACHE_PREFIX . 'feature_stats:' . $date;
            $featureStats = Cache::get($featureStatsKey, []);

            foreach ($featureStats as $feature => $stats) {
                $featureCounts[$feature] = ($featureCounts[$feature] ?? 0) + $stats['usage_count'];
            }
        }

        arsort($featureCounts);
        return array_slice($featureCounts, 0, 10, true);
    }

    /**
     * Get user engagement metrics.
     */
    private function getUserEngagementMetrics(int $days): array
    {
        $totalUsers = 0;
        $activeUsers = [];
        $totalSessions = 0;

        for ($i = 0; $i < $days; $i++) {
            $date = now()->subDays($i)->format('Y-m-d');
            $loginStatsKey = self::CACHE_PREFIX . 'login_stats:' . $date;
            $loginStats = Cache::get($loginStatsKey, []);

            $dailyUsers = $loginStats['unique_users'] ?? [];
            foreach ($dailyUsers as $userId => $email) {
                $activeUsers[$userId] = $email;
            }

            $totalSessions += $loginStats['total_logins'] ?? 0;
        }

        $uniqueActiveUsers = count($activeUsers);

        return [
            'unique_active_users' => $uniqueActiveUsers,
            'total_sessions' => $totalSessions,
            'average_sessions_per_user' => $uniqueActiveUsers > 0 ? round($totalSessions / $uniqueActiveUsers, 2) : 0,
            'daily_active_users' => $this->getDailyActiveUsers($days),
        ];
    }

    /**
     * Get daily active users for the specified period.
     */
    private function getDailyActiveUsers(int $days): array
    {
        $dailyActiveUsers = [];

        for ($i = 0; $i < $days; $i++) {
            $date = now()->subDays($i)->format('Y-m-d');
            $loginStatsKey = self::CACHE_PREFIX . 'login_stats:' . $date;
            $loginStats = Cache::get($loginStatsKey, []);

            $dailyActiveUsers[$date] = count($loginStats['unique_users'] ?? []);
        }

        return array_reverse($dailyActiveUsers, true);
    }

    /**
     * Get real-time activity feed.
     */
    public function getRealtimeActivity(int $limit = 50): array
    {
        $currentHour = now()->format('Y-m-d-H');
        $hourKey = self::CACHE_PREFIX . 'hourly:' . $currentHour;
        
        $activities = Cache::get($hourKey, []);
        
        // Sort by timestamp (most recent first)
        usort($activities, function ($a, $b) {
            return strtotime($b['timestamp']) <=> strtotime($a['timestamp']);
        });

        return array_slice($activities, 0, $limit);
    }

    /**
     * Export activity analytics report.
     */
    public function exportAnalyticsReport(int $days = 30): array
    {
        return [
            'generated_at' => now()->toISOString(),
            'report_period' => [
                'days' => $days,
                'start_date' => now()->subDays($days)->format('Y-m-d'),
                'end_date' => now()->format('Y-m-d'),
            ],
            'analytics' => $this->getUserAnalytics($days),
            'realtime_activity' => $this->getRealtimeActivity(100),
            'summary' => [
                'total_tracked_actions' => $this->getTotalTrackedActions($days),
                'most_active_day' => $this->getMostActiveDay($days),
                'peak_activity_hour' => $this->getPeakActivityHour($days),
            ],
        ];
    }

    /**
     * Get total tracked actions for the period.
     */
    private function getTotalTrackedActions(int $days): int
    {
        $total = 0;

        for ($i = 0; $i < $days; $i++) {
            $date = now()->subDays($i)->format('Y-m-d');
            $dailyStats = $this->getDailyStatistics($date);
            $total += $dailyStats['total_actions'];
        }

        return $total;
    }

    /**
     * Get the most active day in the period.
     */
    private function getMostActiveDay(int $days): array
    {
        $maxActions = 0;
        $mostActiveDay = null;

        for ($i = 0; $i < $days; $i++) {
            $date = now()->subDays($i)->format('Y-m-d');
            $dailyStats = $this->getDailyStatistics($date);
            
            if ($dailyStats['total_actions'] > $maxActions) {
                $maxActions = $dailyStats['total_actions'];
                $mostActiveDay = $date;
            }
        }

        return [
            'date' => $mostActiveDay,
            'total_actions' => $maxActions,
        ];
    }

    /**
     * Get peak activity hour across the period.
     */
    private function getPeakActivityHour(int $days): array
    {
        $hourlyActivity = [];

        for ($i = 0; $i < $days * 24; $i++) {
            $datetime = now()->subHours($i);
            $hourKey = self::CACHE_PREFIX . 'hourly:' . $datetime->format('Y-m-d-H');
            $activities = Cache::get($hourKey, []);
            
            $hour = $datetime->format('H');
            $hourlyActivity[$hour] = ($hourlyActivity[$hour] ?? 0) + count($activities);
        }

        $peakHour = array_keys($hourlyActivity, max($hourlyActivity))[0] ?? '00';

        return [
            'hour' => $peakHour,
            'total_actions' => $hourlyActivity[$peakHour] ?? 0,
            'hourly_breakdown' => $hourlyActivity,
        ];
    }
}