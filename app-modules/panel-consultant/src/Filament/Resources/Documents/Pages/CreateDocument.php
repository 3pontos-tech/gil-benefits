<?php

declare(strict_types=1);

namespace TresPontosTech\PanelConsultant\Filament\Resources\Documents\Pages;

use Filament\Resources\Pages\CreateRecord;
use TresPontosTech\Consultants\Actions\SyncDocumentTypeAction;
use TresPontosTech\Consultants\Models\Document;
use TresPontosTech\PanelConsultant\Filament\Resources\Documents\DocumentResource;

/**
 * @property-read Document $record
 */
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

    /**
     * O tipo segue o arquivo ou o link que o documento tem (`SyncDocumentTypeAction`).
     */
    protected function afterCreate(): void
    {
        resolve(SyncDocumentTypeAction::class)->handle($this->record);
    }
}
