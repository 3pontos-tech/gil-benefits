<?php

namespace TresPontosTech\Vouchers\Filament\Company\Resources\Vouchers\Pages;

use Filament\Resources\Pages\CreateRecord;
use Ramsey\Uuid\Uuid;
use TresPontosTech\Vouchers\Filament\Admin\Resources\Vouchers\VoucherResource;

class CreateVoucher extends CreateRecord
{
    protected static string $resource = VoucherResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['code'] = Uuid::uuid4()->toString();

        return $data;
    }
}
