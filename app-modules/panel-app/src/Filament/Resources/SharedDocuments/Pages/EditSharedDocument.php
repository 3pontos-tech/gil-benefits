<?php

declare(strict_types=1);

namespace TresPontosTech\PanelApp\Filament\Resources\SharedDocuments\Pages;

use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use TresPontosTech\PanelApp\Filament\Resources\SharedDocuments\SharedDocumentResource;

class EditSharedDocument extends EditRecord
{
    protected static string $resource = SharedDocumentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
