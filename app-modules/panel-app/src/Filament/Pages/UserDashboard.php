<?php

declare(strict_types=1);

namespace TresPontosTech\App\Filament\Pages;

use Filament\Pages\Dashboard;
use TresPontosTech\App\Filament\Widgets\FinancialTopicsWidget;
use TresPontosTech\App\Filament\Widgets\JourneyHeroWidget;
use TresPontosTech\App\Filament\Widgets\NextAppointmentWidget;
use TresPontosTech\App\Filament\Widgets\PlanCreditsWidget;
use TresPontosTech\App\Filament\Widgets\SharedMaterialsWidget;

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
