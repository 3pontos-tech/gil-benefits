<?php

declare(strict_types=1);

namespace TresPontosTech\Permissions\Filament\Admin\Resources\Permissions\Pages;

use Filament\Resources\Pages\CreateRecord;
use TresPontosTech\Permissions\Filament\Admin\Resources\Permissions\RoleResource;

class CreateRole extends CreateRecord
{
    protected static string $resource = RoleResource::class;

    protected function getHeaderActions(): array
    {
        return [

        ];
    }
}
