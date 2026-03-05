<?php

namespace TresPontosTech\Admin\Filament\Resources\Prices\Pages;

use Filament\Resources\Pages\CreateRecord;
use TresPontosTech\Admin\Filament\Resources\Prices\PriceResource;

class CreatePrice extends CreateRecord
{
    protected static string $resource = PriceResource::class;

    protected function getHeaderActions(): array
    {
        return [

        ];
    }
}
