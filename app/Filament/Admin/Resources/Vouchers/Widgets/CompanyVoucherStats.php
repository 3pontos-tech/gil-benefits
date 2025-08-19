<?php

namespace App\Filament\Admin\Resources\Vouchers\Widgets;

use App\Enums\VoucherStatusEnum;
use App\Models\Voucher;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class CompanyVoucherStats extends StatsOverviewWidget
{
    protected function getStats(): array
    {

        $activeVouchersCount = Voucher::query()
            ->where('status', VoucherStatusEnum::Active)
            ->where('valid_until', '>=', now())
            ->count();

        $requestedVouchersCount = Voucher::query()
            ->where('status', VoucherStatusEnum::Requested)
            ->count();

        $expiredVouchersCount = Voucher::query()
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
