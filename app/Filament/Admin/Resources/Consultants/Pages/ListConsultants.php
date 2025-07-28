<?php

namespace App\Filament\Admin\Resources\Consultants\Pages;

use App\Filament\Admin\Resources\Consultants\ConsultantResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

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
