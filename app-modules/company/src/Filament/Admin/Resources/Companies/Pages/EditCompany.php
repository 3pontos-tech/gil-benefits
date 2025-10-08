<?php

namespace TresPontosTech\Company\Filament\Admin\Resources\Companies\Pages;

use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use TresPontosTech\Company\Filament\Admin\Resources\Companies\Actions\AttachPlanAction;
use TresPontosTech\Company\Filament\Admin\Resources\Companies\CompanyResource;
use TresPontosTech\Tenant\Filament\Widgets\TenantPlanStatusStats;

class EditCompany extends EditRecord
{
    protected static string $resource = CompanyResource::class;

    protected function getHeaderWidgets(): array
    {
        return [
            TenantPlanStatusStats::class,
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
            AttachPlanAction::make()
                ->disabled($this->record->hasActivePlan())
                ->after(fn () => $this->redirect($this->getResource()::getUrl('edit', ['record' => $this->record]))),
        ];
    }
}
