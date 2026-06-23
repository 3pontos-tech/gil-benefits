<?php

declare(strict_types=1);

namespace TresPontosTech\PanelAdmin\Filament\Resources\Plans\Pages;

use Filament\Resources\Pages\CreateRecord;
use TresPontosTech\PanelAdmin\Filament\Resources\Plans\PlanResource;

class CreatePlan extends CreateRecord
{
    protected static string $resource = PlanResource::class;

    protected function getHeaderActions(): array
    {
        return [

        ];
    }
}
