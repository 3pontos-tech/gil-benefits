<?php

declare(strict_types=1);

namespace TresPontosTech\Permissions\Filament\Admin\Resources\Permissions\Pages;

use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use TresPontosTech\Permissions\Filament\Admin\Resources\Permissions\RoleResource;

class EditRole extends EditRecord
{
    protected static string $resource = RoleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
