<?php

declare(strict_types=1);

namespace TresPontosTech\PanelAdmin\Filament\Resources\ContractualPlans\Pages;

use Filament\Resources\Pages\CreateRecord;
use TresPontosTech\Billing\Core\Enums\BillingProviderEnum;
use TresPontosTech\PanelAdmin\Filament\Resources\ContractualPlans\ContractualPlanResource;

class CreateContractualPlan extends CreateRecord
{
    protected static string $resource = ContractualPlanResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['provider'] = BillingProviderEnum::Contractual->value;

        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [];
    }
}
