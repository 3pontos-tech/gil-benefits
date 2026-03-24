<?php

namespace TresPontosTech\Admin\Filament\Resources\Consultants\Pages;

use Filament\Resources\Pages\CreateRecord;
use TresPontosTech\Admin\Filament\Resources\Consultants\ConsultantResource;

class CreateConsultant extends CreateRecord
{
    protected static string $resource = ConsultantResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['short_description'] ??= '';

        $data['readme'] ??= '';

        $data['biography'] ??= '';

        return $data;
    }
}
