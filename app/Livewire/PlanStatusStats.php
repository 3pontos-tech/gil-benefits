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
        $tenant = Filament::getTenant();

        $this->record = $this->record ?? auth()->user()->ownedCompanies()->with('plans')->first();
        $company = $this->record->where('name', $tenant->name)->first();
        $activePlan = $company->plans()->wherePivot('status', 'active')->first();

        $usedVouchersCount = $this->record->vouchers()
            ->where('status', 'used')
            ->where('valid_until', '>=', now())
            ->count();

        $vouchersCount = $this->record->vouchers()->count();

        return [
            Stat::make('Plano', $activePlan?->name ?? 'N/A')
                ->description($activePlan?->type->value ?? 'N/A'),
            Stat::make('Horas incluídas', $activePlan?->hours_included ?? 'N/A'),
            Stat::make('Data de renovação', $activePlan?->renewal_date?->format('d/m/Y') ?? 'N/A'),
            Stat::make('Total de vouchers', $vouchersCount),
            Stat::make('Vouchers Usados', $usedVouchersCount),
        ];
    }
}
