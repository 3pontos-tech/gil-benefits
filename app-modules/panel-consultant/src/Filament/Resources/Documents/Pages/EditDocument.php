<?php

declare(strict_types=1);

namespace TresPontosTech\PanelConsultant\Filament\Resources\Documents\Pages;

use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;
use TresPontosTech\Consultants\Actions\SyncDocumentTypeAction;
use TresPontosTech\Consultants\Models\Document;
use TresPontosTech\PanelConsultant\Filament\Resources\Documents\DocumentResource;

/**
 * @property-read Document $record
 */
class EditDocument extends EditRecord
{
    protected static string $resource = DocumentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }

    /**
     * O tipo segue o arquivo ou o link que o documento tem, e a escolha "Link" do consultor
     * vale mesmo com um arquivo anexado antes (`SyncDocumentTypeAction`).
     */
    protected function afterSave(): void
    {
        resolve(SyncDocumentTypeAction::class)->handle($this->record, chosenAsLink: data_get($this->data, '_document_type') === 'link');
    }
}
