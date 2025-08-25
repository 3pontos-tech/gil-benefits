<?php

namespace App\Filament\App\Widgets;

use App\Enums\VoucherStatusEnum;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class UserStatsOverview extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        return [
            $this->mountAvailableVouchers(),
            //            $this->mountNextAppointment(),
        ];
    }

    private function mountAvailableVouchers(): Stat
    {
        $tenant = filament()->getTenant()->load('vouchers');

        $availableVouchers = $tenant->vouchers()
            ->where('status', VoucherStatusEnum::Active)
            ->where('valid_until', '>=', now())
            ->count();

        return Stat::make('Available Vouchers', $availableVouchers)
            ->icon('heroicon-s-clock');
    }
}
