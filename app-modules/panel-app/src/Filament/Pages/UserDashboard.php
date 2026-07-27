<?php

declare(strict_types=1);

namespace TresPontosTech\PanelApp\Filament\Pages;

use Filament\Pages\Dashboard;
use TresPontosTech\PanelApp\Filament\Widgets\JourneyHeroWidget;
use TresPontosTech\PanelApp\Filament\Widgets\LatestAppointmentsWidget;
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
        // 12 colunas para conseguir a proporção ~7/5 entre a lista de
        // consultorias e o card de plano, que 6 colunas não permitem.
        return 12;
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
            LatestAppointmentsWidget::make(),
            // A ordem importa: a lista de consultorias ocupa duas linhas da
            // grade, então plano e materiais se empilham na coluna à direita.
            PlanCreditsWidget::make(),
            SharedMaterialsWidget::make(),
        ];
    }
}
