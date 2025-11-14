<?php

namespace App\Filament\Admin\Widgets;

use Filament\Widgets\Widget;

class AppointmentHistoryWidget extends Widget
{
    protected string $view = 'filament.admin.widgets.appointment-history-widget';

    protected function getViewData(): array
    {
        return [
            'appointments' => auth()->user()->appointments()->get(),
        ];
    }
}
