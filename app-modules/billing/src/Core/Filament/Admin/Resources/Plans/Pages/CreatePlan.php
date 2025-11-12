<?php

namespace TresPontosTech\Billing\Core\Filament\Admin\Resources\Plans\Pages;

use TresPontosTech\Billing\Core\Filament\Admin\Resources\Plans\PlanResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePlan extends CreateRecord
{
    protected static string $resource = PlanResource::class;

    protected function getHeaderActions(): array
    {
        return [

        ];
    }
}
