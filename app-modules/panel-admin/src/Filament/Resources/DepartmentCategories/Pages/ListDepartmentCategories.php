<?php

declare(strict_types=1);

namespace TresPontosTech\Admin\Filament\Resources\DepartmentCategories\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use TresPontosTech\Admin\Filament\Resources\DepartmentCategories\DepartmentCategoryResource;

class ListDepartmentCategories extends ListRecords
{
    protected static string $resource = DepartmentCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
