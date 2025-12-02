<?php

declare(strict_types=1);

namespace App\Filament\Admin\Widgets;

use App\Services\Monitoring\DatabaseQueryAnalyzer;
use Filament\Widgets\ChartWidget;

class DatabaseAnalyticsWidget extends ChartWidget
{
    protected static ?int $sort = 3;

    public function __construct(
        private readonly DatabaseQueryAnalyzer $queryAnalyzer
    ) {
        parent::__construct();
    }

    protected function getPollingInterval(): ?string
    {
        return '60s';
    }

    public function getHeading(): string
    {
        return 'Database Query Analytics';
    }

    protected function getData(): array
    {
        $stats = $this->queryAnalyzer->getQueryStatistics();
        $historical = $this->queryAnalyzer->getHistoricalStatistics(24);

        // Prepare data for the last 12 hours
        $labels = [];
        $queryData = [];
        $slowQueryData = [];

        $hours = array_slice($historical, -12, 12, true);
        
        foreach ($hours as $hour => $data) {
            $labels[] = date('H:i', strtotime($hour));
            $queryData[] = $data['total_queries'] ?? 0;
            $slowQueryData[] = $data['slow_queries'] ?? 0;
        }

        // If no historical data, show current stats
        if (empty($labels)) {
            $labels = ['Current'];
            $queryData = [$stats['total_queries'] ?? 0];
            $slowQueryData = [$stats['slow_queries'] ?? 0];
        }

        return [
            'datasets' => [
                [
                    'label' => 'Total Queries',
                    'data' => $queryData,
                    'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                    'borderColor' => 'rgb(59, 130, 246)',
                    'borderWidth' => 2,
                    'fill' => true,
                ],
                [
                    'label' => 'Slow Queries',
                    'data' => $slowQueryData,
                    'backgroundColor' => 'rgba(239, 68, 68, 0.1)',
                    'borderColor' => 'rgb(239, 68, 68)',
                    'borderWidth' => 2,
                    'fill' => true,
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
                    'beginAtZero' => true,
                ],
            ],
        ];
    }

    public function getDescription(): ?string
    {
        $stats = $this->queryAnalyzer->getQueryStatistics();
        
        $totalQueries = $stats['total_queries'] ?? 0;
        $slowQueries = $stats['slow_queries'] ?? 0;
        $avgTime = $stats['average_time'] ?? 0;
        
        return "Total: {$totalQueries} queries | Slow: {$slowQueries} | Avg: {$avgTime}ms";
    }
}