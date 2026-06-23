<?php

declare(strict_types=1);

namespace TresPontosTech\PanelAdmin\Filament\Resources\Consultants\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use TresPontosTech\PanelAdmin\Filament\Resources\Consultants\ConsultantResource;

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
