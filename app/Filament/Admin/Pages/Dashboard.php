<?php

namespace App\Filament\Admin\Pages;

use App\Filament\Admin\Widgets\SystemHealthWidget;
use App\Filament\Admin\Widgets\PerformanceMetricsWidget;
use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    protected int|string|array $columnSpan = 'full';

    public function getColumns(): int|array
    {
        return [
            'xl' => 2,
        ];
    }

    public function getWidgets(): array
    {
        return [
            SystemHealthWidget::class,
            PerformanceMetricsWidget::class,
        ];
    }
}
