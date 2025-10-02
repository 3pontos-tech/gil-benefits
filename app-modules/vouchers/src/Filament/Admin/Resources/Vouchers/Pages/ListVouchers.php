<?php

namespace TresPontosTech\Vouchers\Filament\Admin\Resources\Vouchers\Pages;

use App\Enums\VoucherStatusEnum;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use TresPontosTech\Vouchers\Filament\Admin\Resources\Vouchers\VoucherResource;
use TresPontosTech\Vouchers\Filament\Admin\Resources\Vouchers\Widgets\CompanyVoucherStats;
use TresPontosTech\Vouchers\Models\Voucher;

class ListVouchers extends ListRecords
{
    protected static string $resource = VoucherResource::class;

    protected function getHeaderWidgets(): array
    {
        return [
            CompanyVoucherStats::make(),
        ];
    }

    public function getTabs(): array
    {
        return [
            Tab::make()
                ->label(__('Todos'))
                ->badgeColor('gray')
                ->icon('heroicon-o-inbox'),

            ...collect(VoucherStatusEnum::cases())->map(fn (VoucherStatusEnum $status): Tab => Tab::make()
                ->label($status->getLabel())
                ->badgeColor($status->getColor())
                ->modifyQueryUsing(fn ($query) => $query->where('status', $status))
                ->badge(fn ($query) => Voucher::query()->where('status', $status)->count())
                ->icon($status->getIcon())
            )->toArray(),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
