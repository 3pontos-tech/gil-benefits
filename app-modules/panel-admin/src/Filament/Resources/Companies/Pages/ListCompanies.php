<?php

namespace TresPontosTech\Admin\Filament\Resources\Companies\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use TresPontosTech\Admin\Filament\Resources\Companies\CompanyResource;

class ListCompanies extends ListRecords
{
    protected static string $resource = CompanyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
