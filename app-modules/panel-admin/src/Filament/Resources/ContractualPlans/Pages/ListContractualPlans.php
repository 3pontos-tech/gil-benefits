<?php

namespace TresPontosTech\Admin\Filament\Resources\ContractualPlans\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use TresPontosTech\Admin\Filament\Resources\ContractualPlans\ContractualPlanResource;

class ListContractualPlans extends ListRecords
{
    protected static string $resource = ContractualPlanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
