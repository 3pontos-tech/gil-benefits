<?php

declare(strict_types=1);

namespace TresPontosTech\Consultants\Filament\Resources\Documents\Pages;

use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;
use TresPontosTech\Consultants\Enums\DocumentExtensionTypeEnum;
use TresPontosTech\Consultants\Filament\Resources\Documents\DocumentResource;

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

    protected function afterSave(): void
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
