<?php

namespace App\Filament\Admin\Clusters\Partners\Resources\Tags\Pages;

use App\Filament\Admin\Clusters\Partners\Resources\Tags\TagResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditTag extends EditRecord
{
    protected static string $resource = TagResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
