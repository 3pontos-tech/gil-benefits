<?php

declare(strict_types=1);

namespace App\Filament\Admin\Widgets;

use App\Services\Monitoring\PerformanceMetricsCollector;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class PerformanceMetricsWidget extends BaseWidget
{
    protected static ?int $sort = 2;

    public function __construct(
        private readonly PerformanceMetricsCollector $metricsCollector
    ) {
        parent::__construct();
    }

    protected function getPollingInterval(): ?string
    {
        return '30s';
    }

    protected function getStats(): array
    {
        $metrics = $this->metricsCollector->collectMetrics();

        return [
            Stat::make('Avg Response Time', $this->getResponseTime($metrics))
                ->description($this->getResponseTimeDescription($metrics))
                ->descriptionIcon('heroicon-m-clock')
                ->color($this->getResponseTimeColor($metrics)),

            Stat::make('Database Performance', $this->getDatabaseTime($metrics))
                ->description('Connection time')
                ->descriptionIcon('heroicon-m-circle-stack')
                ->color($this->getDatabaseColor($metrics)),

            Stat::make('Cache Performance', $this->getCacheTime($metrics))
                ->description('Cache response time')
                ->descriptionIcon('heroicon-m-bolt')
                ->color($this->getCacheColor($metrics)),

            Stat::make('Application Uptime', $this->getUptime($metrics))
                ->description('Since last restart')
                ->descriptionIcon('heroicon-m-server')
                ->color('success'),
        ];
    }

    private function getResponseTime(array $metrics): string
    {
        $responseTime = $metrics['response_times']['average_ms'] ?? null;
        
        if ($responseTime === null) {
            return 'N/A';
        }

        if ($responseTime < 1000) {
            return round($responseTime) . 'ms';
        }

        return round($responseTime / 1000, 1) . 's';
    }

    private function getResponseTimeDescription(array $metrics): string
    {
        $p95 = $metrics['response_times']['p95_ms'] ?? null;
        $sampleSize = $metrics['response_times']['sample_size'] ?? 0;

        if ($p95 === null || $sampleSize === 0) {
            return 'No recent data';
        }

        $p95Display = $p95 < 1000 ? round($p95) . 'ms' : round($p95 / 1000, 1) . 's';
        return "P95: {$p95Display} ({$sampleSize} samples)";
    }

    private function getDatabaseTime(array $metrics): string
    {
        $dbTime = $metrics['database']['connection_time_ms'] ?? null;
        
        if ($dbTime === null) {
            return 'N/A';
        }

        return round($dbTime) . 'ms';
    }

    private function getCacheTime(array $metrics): string
    {
        $cacheTime = $metrics['cache']['response_time_ms'] ?? null;
        
        if ($cacheTime === null) {
            return 'N/A';
        }

        return round($cacheTime) . 'ms';
    }

    private function getUptime(array $metrics): string
    {
        $uptime = $metrics['application']['uptime_seconds'] ?? 0;
        
        if ($uptime < 60) {
            return $uptime . 's';
        } elseif ($uptime < 3600) {
            return round($uptime / 60) . 'm';
        } elseif ($uptime < 86400) {
            return round($uptime / 3600, 1) . 'h';
        }

        return round($uptime / 86400, 1) . 'd';
    }

    private function getResponseTimeColor(array $metrics): string
    {
        $responseTime = $metrics['response_times']['average_ms'] ?? 0;

        if ($responseTime > 5000) {
            return 'danger';
        } elseif ($responseTime > 2000) {
            return 'warning';
        }

        return 'success';
    }

    private function getDatabaseColor(array $metrics): string
    {
        $dbTime = $metrics['database']['connection_time_ms'] ?? 0;

        if ($dbTime > 1000) {
            return 'danger';
        } elseif ($dbTime > 500) {
            return 'warning';
        }

        return 'success';
    }

    private function getCacheColor(array $metrics): string
    {
        $cacheTime = $metrics['cache']['response_time_ms'] ?? 0;

        if ($cacheTime > 100) {
            return 'danger';
        } elseif ($cacheTime > 50) {
            return 'warning';
        }

        return 'success';
    }
}