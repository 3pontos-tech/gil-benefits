<?php

namespace App\Filament\Admin\Clusters\Partners\Resources\Consultants\Pages;

use App\Filament\Admin\Clusters\Partners\Resources\Consultants\ConsultantResource;
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
