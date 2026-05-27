<?php

declare(strict_types=1);

namespace TresPontosTech\PanelCompany\Filament\Resources\Departments\Pages;

use Filament\Resources\Pages\CreateRecord;
use TresPontosTech\PanelCompany\Filament\Resources\Departments\DepartmentResource;

class CreateDepartment extends CreateRecord
{
    protected static string $resource = DepartmentResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['company_id'] = filament()->getTenant()?->getKey();

        return $data;
    }
}
