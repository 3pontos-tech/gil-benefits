<?php

declare(strict_types=1);

namespace App\Filament\Admin\Widgets;

use App\Services\Monitoring\DatabaseQueryAnalyzer;
use App\Services\Monitoring\MonitoringDashboard;
use App\Services\Monitoring\UserActivityTracker;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Cache;

class PerformanceAnalyticsWidget extends ChartWidget
{
    protected static ?int $sort = 2;

    protected int | string | array $columnSpan = 'full';

    public function getHeading(): string
    {
        return 'Performance Analytics';
    }

    public function getPollingInterval(): ?string
    {
        return '60s';
    }

    public function __construct(
        private readonly MonitoringDashboard $dashboard,
        private readonly DatabaseQueryAnalyzer $queryAnalyzer,
        private readonly UserActivityTracker $activityTracker
    ) {
        parent::__construct();
    }

    protected function getData(): array
    {
        $hours = 24;
        $labels = [];
        $responseTimeData = [];
        $memoryUsageData = [];
        $queryTimeData = [];
        $activeUsersData = [];

        // Generate labels and collect data for the last 24 hours
        for ($i = $hours - 1; $i >= 0; $i--) {
            $time = now()->subHours($i);
            $labels[] = $time->format('H:i');

            // Get performance metrics
            $metricsKey = "performance_metrics:history:" . $time->format('Y-m-d-H');
            $metrics = Cache::get($metricsKey, []);

            if (!empty($metrics)) {
                $latestMetric = end($metrics);
                $responseTimeData[] = round($latestMetric['response_times']['average_ms'] ?? 0, 2);
                $memoryUsageData[] = round($latestMetric['memory']['usage_percentage'] ?? 0, 2);
            } else {
                $responseTimeData[] = 0;
                $memoryUsageData[] = 0;
            }

            // Get query performance data
            $queryStatsKey = "query_analyzer:stats:" . $time->format('Y-m-d-H');
            $queryStats = Cache::get($queryStatsKey, []);
            $queryTimeData[] = round($queryStats['average_time'] ?? 0, 2);

            // Get active users data
            $activityKey = "user_activity:hourly:" . $time->format('Y-m-d-H');
            $activities = Cache::get($activityKey, []);
            $uniqueUsers = count(array_unique(array_column($activities, 'user_id')));
            $activeUsersData[] = $uniqueUsers;
        }

        return [
            'datasets' => [
                [
                    'label' => 'Response Time (ms)',
                    'data' => $responseTimeData,
                    'borderColor' => 'rgb(59, 130, 246)',
                    'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                    'yAxisID' => 'y',
                ],
                [
                    'label' => 'Memory Usage (%)',
                    'data' => $memoryUsageData,
                    'borderColor' => 'rgb(16, 185, 129)',
                    'backgroundColor' => 'rgba(16, 185, 129, 0.1)',
                    'yAxisID' => 'y1',
                ],
                [
                    'label' => 'Query Time (ms)',
                    'data' => $queryTimeData,
                    'borderColor' => 'rgb(245, 158, 11)',
                    'backgroundColor' => 'rgba(245, 158, 11, 0.1)',
                    'yAxisID' => 'y',
                ],
                [
                    'label' => 'Active Users',
                    'data' => $activeUsersData,
                    'borderColor' => 'rgb(139, 92, 246)',
                    'backgroundColor' => 'rgba(139, 92, 246, 0.1)',
                    'yAxisID' => 'y2',
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getOptions(): array
    {
        return [
            'responsive' => true,
            'interaction' => [
                'mode' => 'index',
                'intersect' => false,
            ],
            'plugins' => [
                'title' => [
                    'display' => true,
                    'text' => 'System Performance Metrics (Last 24 Hours)',
                ],
                'legend' => [
                    'display' => true,
                    'position' => 'top',
                ],
            ],
            'scales' => [
                'x' => [
                    'display' => true,
                    'title' => [
                        'display' => true,
                        'text' => 'Time',
                    ],
                ],
                'y' => [
                    'type' => 'linear',
                    'display' => true,
                    'position' => 'left',
                    'title' => [
                        'display' => true,
                        'text' => 'Time (ms)',
                    ],
                ],
                'y1' => [
                    'type' => 'linear',
                    'display' => true,
                    'position' => 'right',
                    'title' => [
                        'display' => true,
                        'text' => 'Memory Usage (%)',
                    ],
                    'grid' => [
                        'drawOnChartArea' => false,
                    ],
                    'min' => 0,
                    'max' => 100,
                ],
                'y2' => [
                    'type' => 'linear',
                    'display' => false,
                    'position' => 'right',
                    'title' => [
                        'display' => true,
                        'text' => 'Users',
                    ],
                ],
            ],
        ];
    }
}