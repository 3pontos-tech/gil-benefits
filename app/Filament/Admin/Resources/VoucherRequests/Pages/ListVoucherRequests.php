<?php

namespace App\Filament\Admin\Resources\VoucherRequests\Pages;

use App\Filament\Admin\Resources\VoucherRequests\VoucherRequestResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListVoucherRequests extends ListRecords
{
    protected static string $resource = VoucherRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
