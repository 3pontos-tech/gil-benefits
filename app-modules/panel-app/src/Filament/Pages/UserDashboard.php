<?php

namespace TresPontosTech\App\Filament\Pages;

use Filament\Pages\Dashboard;
use TresPontosTech\App\Filament\Widgets\AppointmentHistoryWidget;
use TresPontosTech\App\Filament\Widgets\LatestAppointmentWidget;
use TresPontosTech\App\Filament\Widgets\UserCurrentPlanWidget;

class UserDashboard extends Dashboard
{
    public function getColumns(): int|array
    {
        return 6;
    }

    public function getWidgets(): array
    {
        return [
            UserCurrentPlanWidget::make(),
            LatestAppointmentWidget::make(),
            AppointmentHistoryWidget::make(),
        ];
    }
}
