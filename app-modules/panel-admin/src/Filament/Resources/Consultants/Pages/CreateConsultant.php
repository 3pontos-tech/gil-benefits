<?php

declare(strict_types=1);

namespace TresPontosTech\PanelAdmin\Filament\Resources\Consultants\Pages;

use Filament\Resources\Pages\CreateRecord;
use TresPontosTech\PanelAdmin\Filament\Resources\Consultants\ConsultantResource;

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
