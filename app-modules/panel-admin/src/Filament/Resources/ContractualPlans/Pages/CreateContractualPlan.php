<?php

declare(strict_types=1);

namespace TresPontosTech\Admin\Filament\Resources\ContractualPlans\Pages;

use Filament\Resources\Pages\CreateRecord;
use TresPontosTech\Admin\Filament\Resources\ContractualPlans\ContractualPlanResource;
use TresPontosTech\Billing\Core\Enums\BillingProviderEnum;

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
