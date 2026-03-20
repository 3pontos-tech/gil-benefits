<?php

namespace TresPontosTech\PanelCompany\Filament\Widgets;

use Filament\Facades\Filament;
use Filament\Widgets\StatsOverviewWidget;
use Illuminate\Database\Eloquent\Model;

class TenantPlanStatusStats extends StatsOverviewWidget
{
    public ?Model $record = null;

    protected function getStats(): array
    {
        $this->record ?? Filament::getTenant();

        //        $activePlan = $company->plans()->wherePivot('status', 'active')->first();

        return [
            //            Stat::make('Plano ' . $activePlan?->type->getLabel(), $activePlan?->plan->name ?? 'N/A'),
            //            Stat::make('Horas Mensais', $activePlan?->plan->hours_included ?? 'N/A'),
            //            Stat::make('Data de renovação', $activePlan?->subscription_starting_at?->format('d/m/Y') ?? 'N/A'),
        ];
    }
}
