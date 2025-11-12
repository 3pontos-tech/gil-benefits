<?php

namespace TresPontosTech\Billing\Core\Filament\Admin\Resources\Plans\Pages;

use TresPontosTech\Billing\Core\Filament\Admin\Resources\Plans\PlanResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPlans extends ListRecords
{
    protected static string $resource = PlanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
