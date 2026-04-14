<?php

declare(strict_types=1);

namespace TresPontosTech\Consultants\Filament\Pages;

use Filament\Pages\Dashboard;
use TresPontosTech\Consultants\Filament\Widgets\ConsultantAppointmentHistoryWidget;
use TresPontosTech\Consultants\Filament\Widgets\ConsultantLatestAppointmentWidget;
use TresPontosTech\Consultants\Filament\Widgets\ConsultantStatsOverview;

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
