<?php

declare(strict_types=1);

namespace App\Filament\Admin\Pages;

use App\Filament\Admin\Widgets\DatabaseAnalyticsWidget;
use App\Filament\Admin\Widgets\PerformanceMetricsWidget;
use App\Filament\Admin\Widgets\SystemHealthWidget;
use App\Filament\Admin\Widgets\UserActivityWidget;
use App\Services\Monitoring\MonitoringDashboard as MonitoringDashboardService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class MonitoringDashboard extends Page
{
    protected static ?string $navigationLabel = 'Monitoring';
    protected static ?string $title = 'System Monitoring Dashboard';
    protected static ?int $navigationSort = 100;

    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-chart-bar';
    }

    public function getView(): string
    {
        return 'filament.admin.pages.monitoring-dashboard';
    }

    public function __construct(
        private readonly MonitoringDashboardService $dashboardService
    ) {
        parent::__construct();
    }

    protected function getHeaderWidgets(): array
    {
        return [
            SystemHealthWidget::class,
            PerformanceMetricsWidget::class,
        ];
    }

    protected function getFooterWidgets(): array
    {
        return [
            DatabaseAnalyticsWidget::class,
            UserActivityWidget::class,
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('refresh')
                ->label('Refresh Data')
                ->icon('heroicon-m-arrow-path')
                ->action(function () {
                    $this->dashboardService->clearCache();
                    
                    Notification::make()
                        ->title('Dashboard Refreshed')
                        ->success()
                        ->send();
                }),

            Action::make('export')
                ->label('Export Report')
                ->icon('heroicon-m-document-arrow-down')
                ->action(function () {
                    $report = $this->dashboardService->exportMonitoringReport();
                    $filename = 'monitoring_report_' . now()->format('Y-m-d_H-i-s') . '.json';
                    $filepath = storage_path('logs/' . $filename);
                    
                    file_put_contents($filepath, json_encode($report, JSON_PRETTY_PRINT));
                    
                    Notification::make()
                        ->title('Report Exported')
                        ->body("Report saved to: {$filename}")
                        ->success()
                        ->send();
                }),
        ];
    }

    public function getDashboardData(): array
    {
        return $this->dashboardService->getDashboardData();
    }
}