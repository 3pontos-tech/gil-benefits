<?php

namespace TresPontosTech\Billing\Core\Filament\Admin\Resources\Prices\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use TresPontosTech\Billing\Core\Filament\Admin\Resources\Prices\PriceResource;

class ListPrices extends ListRecords
{
    protected static string $resource = PriceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
