<?php

namespace App\Filament\Admin\Resources\Consultants\Pages;

use App\Filament\Admin\Resources\Consultants\ConsultantResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditConsultant extends EditRecord
{
    protected static string $resource = ConsultantResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
