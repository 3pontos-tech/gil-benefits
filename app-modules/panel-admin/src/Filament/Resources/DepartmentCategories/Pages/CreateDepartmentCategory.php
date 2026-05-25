<?php

declare(strict_types=1);

namespace TresPontosTech\Admin\Filament\Resources\DepartmentCategories\Pages;

use Filament\Resources\Pages\CreateRecord;
use TresPontosTech\Admin\Filament\Resources\DepartmentCategories\DepartmentCategoryResource;

class CreateDepartmentCategory extends CreateRecord
{
    protected static string $resource = DepartmentCategoryResource::class;
}
