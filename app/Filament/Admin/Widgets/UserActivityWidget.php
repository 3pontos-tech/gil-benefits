<?php

declare(strict_types=1);

namespace App\Filament\Admin\Widgets;

use App\Services\Monitoring\UserActivityTracker;
use Filament\Widgets\ChartWidget;

class UserActivityWidget extends ChartWidget
{
    protected static ?int $sort = 4;

    public function __construct(
        private readonly UserActivityTracker $activityTracker
    ) {
        parent::__construct();
    }

    protected function getPollingInterval(): ?string
    {
        return '300s';
    }

    public function getHeading(): string
    {
        return 'User Activity (Last 7 Days)';
    }

    protected function getData(): array
    {
        $analytics = $this->activityTracker->getUserAnalytics(7);
        
        $labels = [];
        $activeUsersData = [];
        $totalActionsData = [];

        // Get daily active users for the last 7 days
        $dailyUsers = $analytics['user_engagement']['daily_active_users'] ?? [];
        
        foreach ($dailyUsers as $date => $count) {
            $labels[] = date('M j', strtotime($date));
            $activeUsersData[] = $count;
        }

        // Get daily actions
        foreach ($analytics['daily_stats'] as $date => $stats) {
            $totalActionsData[] = $stats['total_actions'] ?? 0;
        }

        // Ensure arrays are the same length
        while (count($totalActionsData) < count($activeUsersData)) {
            $totalActionsData[] = 0;
        }

        return [
            'datasets' => [
                [
                    'label' => 'Active Users',
                    'data' => $activeUsersData,
                    'backgroundColor' => 'rgba(34, 197, 94, 0.1)',
                    'borderColor' => 'rgb(34, 197, 94)',
                    'borderWidth' => 2,
                    'fill' => true,
                    'yAxisID' => 'y',
                ],
                [
                    'label' => 'Total Actions',
                    'data' => $totalActionsData,
                    'backgroundColor' => 'rgba(168, 85, 247, 0.1)',
                    'borderColor' => 'rgb(168, 85, 247)',
                    'borderWidth' => 2,
                    'fill' => true,
                    'yAxisID' => 'y1',
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
            'plugins' => [
                'legend' => [
                    'display' => true,
                ],
            ],
            'scales' => [
                'y' => [
                    'type' => 'linear',
                    'display' => true,
                    'position' => 'left',
                    'beginAtZero' => true,
                    'title' => [
                        'display' => true,
                        'text' => 'Active Users',
                    ],
                ],
                'y1' => [
                    'type' => 'linear',
                    'display' => true,
                    'position' => 'right',
                    'beginAtZero' => true,
                    'title' => [
                        'display' => true,
                        'text' => 'Total Actions',
                    ],
                    'grid' => [
                        'drawOnChartArea' => false,
                    ],
                ],
            ],
        ];
    }

    public function getDescription(): ?string
    {
        $analytics = $this->activityTracker->getUserAnalytics(7);
        $engagement = $analytics['user_engagement'];
        
        $totalUsers = $engagement['unique_active_users'] ?? 0;
        $totalSessions = $engagement['total_sessions'] ?? 0;
        $avgSessions = $engagement['average_sessions_per_user'] ?? 0;
        
        return "Active users: {$totalUsers} | Sessions: {$totalSessions} | Avg: {$avgSessions} per user";
    }
}