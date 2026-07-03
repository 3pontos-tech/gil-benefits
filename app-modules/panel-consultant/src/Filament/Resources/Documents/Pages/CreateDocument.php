<?php

declare(strict_types=1);

namespace TresPontosTech\PanelConsultant\Filament\Resources\Documents\Pages;

use Filament\Resources\Pages\CreateRecord;
use TresPontosTech\Consultants\Enums\DocumentExtensionTypeEnum;
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

    protected function afterCreate(): void
    {
        $documents = $this->record->getMedia('documents')->isNotEmpty();
        if ($documents) {
            $this->record->update(['link' => null]);
        }

        if (! $documents) {
            $this->record->update(['type' => DocumentExtensionTypeEnum::Link]);
        }

    }
}
