<?php

namespace TresPontosTech\Billing\Core\Filament\Admin\Resources\Plans\Pages;

use Filament\Resources\Pages\CreateRecord;
use TresPontosTech\Billing\Core\Filament\Admin\Resources\Plans\PlanResource;

class CreatePlan extends CreateRecord
{
    protected static string $resource = PlanResource::class;

    protected function getHeaderActions(): array
    {
        return [

        ];
    }
}
