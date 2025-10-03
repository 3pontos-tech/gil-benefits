<?php

namespace TresPontosTech\Vouchers\Filament\Company\Resources\Vouchers\Pages;

use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use TresPontosTech\Vouchers\Filament\Admin\Resources\Vouchers\VoucherResource;

class EditVoucher extends EditRecord
{
    protected static string $resource = VoucherResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
