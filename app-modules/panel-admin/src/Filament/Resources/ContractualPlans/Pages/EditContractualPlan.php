<?php

namespace TresPontosTech\Admin\Filament\Resources\ContractualPlans\Pages;

use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use TresPontosTech\Admin\Filament\Resources\ContractualPlans\ContractualPlanResource;

class EditContractualPlan extends EditRecord
{
    protected static string $resource = ContractualPlanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
