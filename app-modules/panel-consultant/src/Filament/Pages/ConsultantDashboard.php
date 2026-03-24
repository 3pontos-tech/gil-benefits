<?php

namespace TresPontosTech\Consultants\Filament\Pages;

use Filament\Pages\Dashboard;
use TresPontosTech\Consultants\Filament\Widgets\ConsultantAppointmentHistoryWidget;
use TresPontosTech\Consultants\Filament\Widgets\ConsultantLatestAppointmentWidget;

class ConsultantDashboard extends Dashboard
{
    public function getColumns(): int|array
    {
        return 6;
    }

    public function getWidgets(): array
    {
        return [
            ConsultantLatestAppointmentWidget::make(),
            ConsultantAppointmentHistoryWidget::make(),
        ];
    }
}
