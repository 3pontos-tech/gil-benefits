<?php

declare(strict_types=1);

namespace App\Filament\Admin\Widgets;

use App\Services\Monitoring\MonitoringDashboard;
use Filament\Widgets\Widget;
use Illuminate\Contracts\View\View;

class MonitoringDashboardWidget extends Widget
{
    protected static string $view = 'filament.admin.widgets.monitoring-dashboard';

    protected int | string | array $columnSpan = 'full';

    protected static ?int $sort = 1;

    public function __construct(
        private readonly MonitoringDashboard $dashboard
    ) {
        parent::__construct();
    }

    public function getDashboardData(): array
    {
        return $this->dashboard->getDashboardData();
    }

    public function getSystemStatus(): array
    {
        return $this->dashboard->getSystemStatus();
    }

    public function getPerformanceTrends(): array
    {
        return $this->dashboard->getPerformanceTrends(24);
    }

    protected function getViewData(): array
    {
        return [
            'dashboardData' => $this->getDashboardData(),
            'systemStatus' => $this->getSystemStatus(),
            'performanceTrends' => $this->getPerformanceTrends(),
        ];
    }
}