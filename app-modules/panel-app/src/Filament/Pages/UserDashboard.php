<?php

declare(strict_types=1);

namespace TresPontosTech\PanelApp\Filament\Pages;

use Filament\Pages\Dashboard;
use TresPontosTech\PanelApp\Filament\Widgets\FinancialTopicsWidget;
use TresPontosTech\PanelApp\Filament\Widgets\JourneyHeroWidget;
use TresPontosTech\PanelApp\Filament\Widgets\NextAppointmentWidget;
use TresPontosTech\PanelApp\Filament\Widgets\PlanCreditsWidget;
use TresPontosTech\PanelApp\Filament\Widgets\SharedMaterialsWidget;

class UserDashboard extends Dashboard
{
    public function getTitle(): string
    {
        return '';
    }

    public function getColumns(): int|array
    {
        return 6;
    }

    public function getHeading(): string
    {
        return '';
    }

    public static function getNavigationLabel(): string
    {
        return __('panel-app::pages.dashboard.navigation_label');
    }

    public function getWidgets(): array
    {
        return [
            JourneyHeroWidget::make(),
            NextAppointmentWidget::make(),
            PlanCreditsWidget::make(),
            FinancialTopicsWidget::make(),
            SharedMaterialsWidget::make(),
        ];
    }
}
