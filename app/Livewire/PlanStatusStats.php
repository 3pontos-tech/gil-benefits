<?php

namespace App\Livewire;

use Filament\Facades\Filament;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Model;

class PlanStatusStats extends StatsOverviewWidget
{
    public ?Model $record = null;

    protected function getStats(): array
    {
        $company = $this->record ?? Filament::getTenant();

        $activePlan = $company->plans()->wherePivot('status', 'active')->first();

        $usedVouchersCount = $company->vouchers()
            ->where('status', 'used')
            ->where('valid_until', '>=', now())
            ->count();

        return [
            Stat::make('Plano ' . $activePlan?->type->getLabel() , $activePlan?->plan->name ?? 'N/A'),
            Stat::make('Horas Mensais', $activePlan?->plan->hours_included ?? 'N/A'),
            Stat::make('Data de renovação', $activePlan?->subscription_starting_at?->format('d/m/Y') ?? 'N/A'),
            Stat::make('Vouchers Usados', $usedVouchersCount),
        ];
    }
}
