<?php

namespace TresPontosTech\Vouchers\Filament\Company\Resources\Vouchers\Widgets;

use App\Enums\VoucherStatusEnum;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class UserVoucherStats extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $tenant = filament()->getTenant()->load('vouchers');

        $activeVouchersCount = $tenant->vouchers()
            ->where('status', VoucherStatusEnum::Active)
            ->where('valid_until', '>=', now())
            ->count();

        $requestedVouchersCount = $tenant->vouchers()
            ->where('status', VoucherStatusEnum::Requested)
            ->count();

        $expiredVouchersCount = $tenant->vouchers()
            ->where('status', VoucherStatusEnum::Expired)
            ->count();

        return [
            Stat::make('Vouchers Ativos', $activeVouchersCount)
                ->icon('heroicon-o-arrow-trending-up')
                ->description('Prontos para uso')
                ->color('success'),
            Stat::make('Vouchers Pendentes', $requestedVouchersCount)
                ->icon('heroicon-o-arrow-trending-up')
                ->description('Aguardando aprovação')
                ->color('info'),
            Stat::make('Vouchers Expirados', $expiredVouchersCount)
                ->icon('heroicon-o-arrow-trending-up')
                ->description('Vencidos')
                ->color('danger'),
        ];
    }
}
