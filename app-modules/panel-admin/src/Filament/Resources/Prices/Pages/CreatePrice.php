<?php

declare(strict_types=1);

namespace TresPontosTech\PanelAdmin\Filament\Resources\Prices\Pages;

use Filament\Resources\Pages\CreateRecord;
use TresPontosTech\PanelAdmin\Filament\Resources\Prices\PriceResource;

class CreatePrice extends CreateRecord
{
    protected static string $resource = PriceResource::class;

    protected function getHeaderActions(): array
    {
        return [

        ];
    }
}
