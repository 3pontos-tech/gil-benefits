<?php

declare(strict_types=1);

namespace App\Filament\Admin\Widgets;

use App\Services\Monitoring\SystemMonitor;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class SystemHealthWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    public function __construct(
        private readonly SystemMonitor $systemMonitor
    ) {
        parent::__construct();
    }

    protected function getPollingInterval(): ?string
    {
        return '30s';
    }

    protected function getStats(): array
    {
        $health = $this->systemMonitor->checkSystemHealth();

        return [
            Stat::make('System Status', $this->formatStatus($health['status']))
                ->description($this->getStatusDescription($health))
                ->descriptionIcon($this->getStatusIcon($health['status']))
                ->color($this->getStatusColor($health['status'])),

            Stat::make('Database', $this->formatStatus($health['checks']['database']['status'] ?? 'unknown'))
                ->description($this->getDatabaseDescription($health['checks']['database'] ?? []))
                ->descriptionIcon($this->getStatusIcon($health['checks']['database']['status'] ?? 'unknown'))
                ->color($this->getStatusColor($health['checks']['database']['status'] ?? 'unknown')),

            Stat::make('Cache', $this->formatStatus($health['checks']['cache']['status'] ?? 'unknown'))
                ->description($this->getCacheDescription($health['checks']['cache'] ?? []))
                ->descriptionIcon($this->getStatusIcon($health['checks']['cache']['status'] ?? 'unknown'))
                ->color($this->getStatusColor($health['checks']['cache']['status'] ?? 'unknown')),

            Stat::make('Memory Usage', $this->getMemoryUsage($health['checks']['memory'] ?? []))
                ->description($this->getMemoryDescription($health['checks']['memory'] ?? []))
                ->descriptionIcon('heroicon-m-cpu-chip')
                ->color($this->getMemoryColor($health['checks']['memory'] ?? [])),
        ];
    }

    private function formatStatus(string $status): string
    {
        return match ($status) {
            'healthy' => 'Healthy',
            'degraded' => 'Degraded',
            'critical' => 'Critical',
            default => 'Unknown',
        };
    }

    private function getStatusDescription(array $health): string
    {
        $issueCount = 0;
        foreach ($health['checks'] as $check) {
            if (!empty($check['issues'])) {
                $issueCount += count($check['issues']);
            }
        }

        if ($issueCount === 0) {
            return 'All systems operational';
        }

        return $issueCount === 1 ? '1 issue detected' : "{$issueCount} issues detected";
    }

    private function getDatabaseDescription(array $dbCheck): string
    {
        if (isset($dbCheck['response_time_ms'])) {
            return "Response: {$dbCheck['response_time_ms']}ms";
        }

        if (isset($dbCheck['error'])) {
            return 'Connection failed';
        }

        return 'Status unknown';
    }

    private function getCacheDescription(array $cacheCheck): string
    {
        if (isset($cacheCheck['response_time_ms'])) {
            return "Response: {$cacheCheck['response_time_ms']}ms";
        }

        if (isset($cacheCheck['error'])) {
            return 'Cache failed';
        }

        return 'Status unknown';
    }

    private function getMemoryUsage(array $memoryCheck): string
    {
        if (isset($memoryCheck['usage_percentage'])) {
            return "{$memoryCheck['usage_percentage']}%";
        }

        return 'Unknown';
    }

    private function getMemoryDescription(array $memoryCheck): string
    {
        if (isset($memoryCheck['current_usage_mb'], $memoryCheck['limit_mb'])) {
            return "{$memoryCheck['current_usage_mb']} MB / {$memoryCheck['limit_mb']} MB";
        }

        return 'Memory info unavailable';
    }

    private function getStatusIcon(string $status): string
    {
        return match ($status) {
            'healthy' => 'heroicon-m-check-circle',
            'degraded' => 'heroicon-m-exclamation-triangle',
            'critical' => 'heroicon-m-x-circle',
            default => 'heroicon-m-question-mark-circle',
        };
    }

    private function getStatusColor(string $status): string
    {
        return match ($status) {
            'healthy' => 'success',
            'degraded' => 'warning',
            'critical' => 'danger',
            default => 'gray',
        };
    }

    private function getMemoryColor(array $memoryCheck): string
    {
        $usage = $memoryCheck['usage_percentage'] ?? 0;

        if ($usage > 90) {
            return 'danger';
        } elseif ($usage > 80) {
            return 'warning';
        }

        return 'success';
    }
}