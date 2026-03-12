<?php

namespace TresPontosTech\Admin\Filament\Resources\Consultants\Pages;

use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;
use TresPontosTech\Admin\Filament\Resources\Consultants\ConsultantResource;

class EditConsultant extends EditRecord
{
    protected static string $resource = ConsultantResource::class;

    protected function mutateFormDataBeforeFill(array $data): array
    {

        $data['short_description'] ??= '';

        $data['readme'] ??= '';

        $data['biography'] ??= '';

        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }
}
