<?php

namespace TresPontosTech\Admin\Filament\Pages;

use Filament\Pages\Dashboard as BaseDashboard;
use TresPontosTech\Admin\Filament\Widgets\LatestCompanies;
use TresPontosTech\Admin\Filament\Widgets\QuickActions;
use TresPontosTech\Admin\Filament\Widgets\StatsOverview;

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
            StatsOverview::class,
            QuickActions::class,
            LatestCompanies::class,
        ];
    }
}
