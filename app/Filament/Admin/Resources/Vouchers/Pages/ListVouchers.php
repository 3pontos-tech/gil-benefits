<?php

namespace App\Filament\Admin\Resources\Vouchers\Pages;

use App\Filament\Admin\Resources\Vouchers\VoucherResource;
use App\Filament\Admin\Resources\Vouchers\Widgets\CompanyVoucherStats;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListVouchers extends ListRecords
{
    protected static string $resource = VoucherResource::class;

    protected function getHeaderWidgets(): array
    {
        return [
            CompanyVoucherStats::make(),
        ];
    }
    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
