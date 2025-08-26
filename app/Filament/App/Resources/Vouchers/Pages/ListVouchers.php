<?php

namespace App\Filament\App\Resources\Vouchers\Pages;

use App\Filament\Admin\Resources\Vouchers\VoucherResource;
use Filament\Resources\Pages\ListRecords;

class ListVouchers extends ListRecords
{
    protected static string $resource = VoucherResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
