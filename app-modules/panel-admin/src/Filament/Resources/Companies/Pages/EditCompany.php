<?php

namespace TresPontosTech\Admin\Filament\Resources\Companies\Pages;

use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use TresPontosTech\Admin\Filament\Resources\Companies\CompanyResource;
use TresPontosTech\PanelCompany\Filament\Widgets\TenantPlanStatusStats;

class EditCompany extends EditRecord
{
    protected static string $resource = CompanyResource::class;

    protected function getHeaderWidgets(): array
    {
        return [
            TenantPlanStatusStats::class,
        ];
    }

    protected function afterSave(): void
    {
        $this->redirect($this->getResource()::getUrl('edit', [
            'record' => $this->record,
        ]));
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->requiresConfirmation(),
        ];
    }
}
