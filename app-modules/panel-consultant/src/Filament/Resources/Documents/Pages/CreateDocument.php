<?php

declare(strict_types=1);

namespace TresPontosTech\Consultants\Filament\Resources\Documents\Pages;

use Filament\Resources\Pages\CreateRecord;
use TresPontosTech\Consultants\Filament\Resources\Documents\DocumentResource;

class CreateDocument extends CreateRecord
{
    protected static string $resource = DocumentResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $consultant = auth()->user()->consultant;

        $data['documentable_type'] = $consultant->getMorphClass();
        $data['documentable_id'] = $consultant->getKey();

        return $data;
    }
}
