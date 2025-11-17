<?php

namespace App\Filament\Admin\Widgets;

use Filament\Widgets\Widget;
use TresPontosTech\Billing\Core\Models\Plan;

class PlansOverview extends Widget
{
    protected string $view = 'filament.admin.widgets.plans-overview';

    public function getColumnSpan(): int|string|array
    {
        return 'full';
    }

    protected function getViewData(): array
    {
        return [
            'plans' => Plan::query()
                ->where('active', 0)
                ->with('prices')
                ->get(),
        ];
    }
}
