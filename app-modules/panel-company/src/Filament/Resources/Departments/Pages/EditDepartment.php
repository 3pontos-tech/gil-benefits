<?php

declare(strict_types=1);

namespace TresPontosTech\PanelCompany\Filament\Resources\Departments\Pages;

use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use TresPontosTech\PanelCompany\Filament\Resources\Departments\DepartmentResource;

class EditDepartment extends EditRecord
{
    protected static string $resource = DepartmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
