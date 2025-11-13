<?php

namespace TresPontosTech\Billing\Core\Filament\Admin\Resources\Prices\Pages;

use Filament\Resources\Pages\CreateRecord;
use TresPontosTech\Billing\Core\Filament\Admin\Resources\Prices\PriceResource;

class CreatePrice extends CreateRecord
{
    protected static string $resource = PriceResource::class;

    protected function getHeaderActions(): array
    {
        return [

        ];
    }
}
