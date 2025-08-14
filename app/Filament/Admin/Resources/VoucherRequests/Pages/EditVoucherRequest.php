<?php

namespace App\Filament\Admin\Resources\VoucherRequests\Pages;

use App\Filament\Admin\Resources\VoucherRequests\VoucherRequestResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditVoucherRequest extends EditRecord
{
    protected static string $resource = VoucherRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
