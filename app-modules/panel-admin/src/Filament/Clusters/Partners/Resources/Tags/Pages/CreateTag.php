<?php

declare(strict_types=1);

namespace TresPontosTech\PanelAdmin\Filament\Clusters\Partners\Resources\Tags\Pages;

use Filament\Resources\Pages\CreateRecord;
use TresPontosTech\PanelAdmin\Filament\Clusters\Partners\Resources\Tags\TagResource;

class CreateTag extends CreateRecord
{
    protected static string $resource = TagResource::class;
}
