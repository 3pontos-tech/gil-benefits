<?php

namespace TresPontosTech\User\Filament\App\Pages;

use Filament\Pages\Dashboard;
use TresPontosTech\User\Filament\App\Widgets\AppointmentHistoryWidget;
use TresPontosTech\User\Filament\App\Widgets\LatestAppointmentWidget;
use TresPontosTech\User\Filament\App\Widgets\UserCurrentPlanWidget;

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
