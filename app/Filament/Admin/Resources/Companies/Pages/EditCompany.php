<?php

namespace App\Filament\Admin\Resources\Companies\Pages;

use App\Filament\Admin\Resources\Companies\Actions\AttachPlanAction;
use App\Filament\Admin\Resources\Companies\CompanyResource;
use App\Livewire\PlanStatusStats;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;

class EditCompany extends EditRecord
{
    protected static string $resource = CompanyResource::class;

    protected function getHeaderWidgets(): array
    {
        return [
            PlanStatusStats::class,
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
