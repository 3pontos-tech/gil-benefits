<?php

namespace TresPontosTech\Consultants\Filament\Admin\Resources\Consultants\Pages;

use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;
use TresPontosTech\Consultants\Filament\Admin\Resources\Consultants\ConsultantResource;

class EditConsultant extends EditRecord
{
    protected static string $resource = ConsultantResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }
}
