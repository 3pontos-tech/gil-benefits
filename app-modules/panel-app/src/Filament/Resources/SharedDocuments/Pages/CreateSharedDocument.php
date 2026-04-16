<?php

declare(strict_types=1);

namespace TresPontosTech\App\Filament\Resources\SharedDocuments\Pages;

use Filament\Resources\Pages\CreateRecord;
use TresPontosTech\App\Filament\Resources\SharedDocuments\SharedDocumentResource;

class CreateSharedDocument extends CreateRecord
{
    protected static string $resource = SharedDocumentResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['documentable_type'] = auth()->user()->getMorphClass();
        $data['documentable_id'] = auth()->user()->getKey();

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
