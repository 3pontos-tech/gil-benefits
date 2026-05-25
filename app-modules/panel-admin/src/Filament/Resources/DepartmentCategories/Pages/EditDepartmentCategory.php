<?php

declare(strict_types=1);

namespace TresPontosTech\Admin\Filament\Resources\DepartmentCategories\Pages;

use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use TresPontosTech\Admin\Filament\Resources\DepartmentCategories\DepartmentCategoryResource;

class EditDepartmentCategory extends EditRecord
{
    protected static string $resource = DepartmentCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
