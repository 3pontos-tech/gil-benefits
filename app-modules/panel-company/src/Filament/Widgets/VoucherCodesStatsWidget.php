<?php

declare(strict_types=1);

namespace TresPontosTech\PanelCompany\Filament\Widgets;

use Filament\Facades\Filament;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Builder;
use TresPontosTech\Company\Models\Company;
use TresPontosTech\Vouchers\Models\VoucherCode;

class VoucherCodesStatsWidget extends StatsOverviewWidget
{
    protected static bool $isDiscoverable = false;

    protected int|string|array $columnSpan = 'full';

    protected ?string $pollingInterval = null;

    protected function getStats(): array
    {
        /** @var Company $tenant */
        $tenant = Filament::getTenant();

        $codes = VoucherCode::query()
            ->whereHas('batch', fn (Builder $query): Builder => $query->where('company_id', $tenant->getKey()));

        $total = (clone $codes)->count();
        $redeemed = (clone $codes)->where('redemptions_count', '>', 0)->count();

        return [
            Stat::make(__('panel-company::resources.pages.voucher_codes.stats.total'), $total)
                ->icon('heroicon-o-ticket')
                ->color('gray'),
            Stat::make(__('panel-company::resources.pages.voucher_codes.stats.available'), $total - $redeemed)
                ->description(__('panel-company::resources.pages.voucher_codes.stats.available_description'))
                ->icon('heroicon-o-check-circle')
                ->color('success'),
            Stat::make(__('panel-company::resources.pages.voucher_codes.stats.redeemed'), $redeemed)
                ->description(__('panel-company::resources.pages.voucher_codes.stats.redeemed_description'))
                ->icon('heroicon-o-user-circle')
                ->color('info'),
        ];
    }
}
