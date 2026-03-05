<?php

declare(strict_types=1);

namespace TresPontosTech\Admin\Filament\Resources\Permissions\Schemas;

use Filament\Forms\Components\CheckboxList;

class PermissionsCheckboxList extends CheckboxList
{
    protected string $view = 'permissions::filament.pages.permissions-checkbox-list';
}
