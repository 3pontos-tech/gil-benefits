<?php

declare(strict_types=1);

namespace TresPontosTech\PanelAdmin\Filament\Pages;

use Filament\Pages\Dashboard as BaseDashboard;
use TresPontosTech\PanelAdmin\Filament\Widgets\LatestCompanies;
use TresPontosTech\PanelAdmin\Filament\Widgets\QuickActions;
use TresPontosTech\PanelAdmin\Filament\Widgets\StatsOverview;

class Dashboard extends BaseDashboard
{
    /**
     * @var int|string|array<string, int|string>
     */
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
