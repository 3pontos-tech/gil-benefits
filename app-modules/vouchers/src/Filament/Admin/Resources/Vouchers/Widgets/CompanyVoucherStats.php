<?php

namespace TresPontosTech\Vouchers\Filament\Admin\Resources\Vouchers\Widgets;

use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use TresPontosTech\Vouchers\Enums\VoucherStatusEnum;
use TresPontosTech\Vouchers\Models\Voucher;

class CompanyVoucherStats extends StatsOverviewWidget
{
    protected function getStats(): array
    {

        $activeVouchersCount = Voucher::query()
            ->where('status', VoucherStatusEnum::Active)
            ->where('valid_until', '>=', now())
            ->count();

        $pendingVouchersCount = Voucher::query()
            ->where('status', VoucherStatusEnum::Pending)
            ->where('valid_until', '>=', now())
            ->count();

        $usedVouchersCount = Voucher::query()
            ->where('status', VoucherStatusEnum::Used)
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
            Stat::make('Vouchers Disponíveis', $pendingVouchersCount)
                ->icon('heroicon-o-arrow-trending-up')
                ->description('Prontos para uso')
                ->color('warning'),
            Stat::make('Vouchers Pendentes', $requestedVouchersCount)
                ->icon('heroicon-o-arrow-trending-up')
                ->description('Aguardando aprovação')
                ->color('grey'),
            Stat::make('Vouchers Utilizados', $usedVouchersCount)
                ->icon('heroicon-o-arrow-trending-up')
                ->description('Utilizados')
                ->color('info'),
            Stat::make('Vouchers Expirados', $expiredVouchersCount)
                ->icon('heroicon-o-arrow-trending-up')
                ->description('Vencidos')
                ->color('danger'),
        ];
    }
}
