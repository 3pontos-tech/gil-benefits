<?php

namespace TresPontosTech\Consultants\Filament\Admin\Resources\Consultants\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use TresPontosTech\Consultants\Filament\Admin\Resources\Consultants\ConsultantResource;

class ListConsultants extends ListRecords
{
    protected static string $resource = ConsultantResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
