<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Monitoring\SystemMonitor;
use Illuminate\Console\Command;

class MonitorSystemHealthCommand extends Command
{
    protected $signature = 'monitor:health {--json : Output as JSON}';

    protected $description = 'Check system health and report status';

    public function handle(SystemMonitor $monitor): int
    {
        $this->info('Checking system health...');

        $healthStatus = $monitor->checkSystemHealth();

        if ($this->option('json')) {
            $this->line(json_encode($healthStatus, JSON_PRETTY_PRINT));

            return 0;
        }

        $this->displayHealthStatus($healthStatus);

        return $healthStatus['status'] === 'healthy' ? 0 : 1;
    }

    private function displayHealthStatus(array $status): void
    {
        $this->newLine();
        $this->line("Overall Status: <fg={$this->getStatusColor($status['status'])}>{$status['status']}</>");
        $this->newLine();

        foreach ($status['checks'] as $checkName => $result) {
            $color = $this->getStatusColor($result['status']);
            $this->line("  {$checkName}: <fg={$color}>{$result['status']}</>");

            if (! empty($result['issues'])) {
                foreach ($result['issues'] as $issue) {
                    $this->line("    - <fg=red>{$issue}</>");
                }
            }

            // Show additional details for some checks
            if (isset($result['response_time_ms'])) {
                $this->line("    Response time: {$result['response_time_ms']}ms");
            }

            if (isset($result['usage_percent'])) {
                $this->line("    Usage: {$result['usage_percent']}%");
            }
        }

        $this->newLine();
        $this->line("Timestamp: {$status['timestamp']}");
    }

    private function getStatusColor(string $status): string
    {
        return match ($status) {
            'healthy' => 'green',
            'degraded' => 'yellow',
            'critical' => 'red',
            default => 'white',
        };
    }
}
