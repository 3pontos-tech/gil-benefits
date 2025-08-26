<?php

namespace App\Filament\Admin\Clusters\Partners\Resources\Tags\Pages;

use App\Filament\Admin\Clusters\Partners\Resources\Tags\TagResource;
use Filament\Resources\Pages\CreateRecord;

class CreateTag extends CreateRecord
{
    protected static string $resource = TagResource::class;
}
