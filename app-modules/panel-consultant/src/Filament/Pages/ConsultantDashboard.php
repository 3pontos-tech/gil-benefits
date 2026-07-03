<?php

declare(strict_types=1);

namespace TresPontosTech\PanelConsultant\Filament\Pages;

use Filament\Pages\Dashboard;
use TresPontosTech\PanelConsultant\Filament\Widgets\ConsultantAppointmentHistoryWidget;
use TresPontosTech\PanelConsultant\Filament\Widgets\ConsultantLatestAppointmentWidget;
use TresPontosTech\PanelConsultant\Filament\Widgets\ConsultantStatsOverview;

class ConsultantDashboard extends Dashboard
{
    public function getColumns(): int|array
    {
        return 8;
    }

    public function getWidgets(): array
    {
        return [
            ConsultantStatsOverview::make(),
            ConsultantLatestAppointmentWidget::make(),
            ConsultantAppointmentHistoryWidget::make(),
        ];
    }
}
