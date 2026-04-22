<?php

declare(strict_types=1);

namespace TresPontosTech\Admin\Filament\Resources\Appointments\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use TresPontosTech\Admin\Filament\Resources\Appointments\AppointmentResource;
use TresPontosTech\Admin\Filament\Widgets\AppointmentsStatsOverview;

class ListAppointments extends ListRecords
{
    protected static string $resource = AppointmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            AppointmentsStatsOverview::class,
        ];
    }

    public function mount(): void
    {
        parent::mount();

        $persisted = session($this->getTableFiltersSessionKey());

        if (filled($persisted)) {
            $this->dispatch('appointments-table-filters-changed', filters: $persisted);
        }
    }

    protected function handleTableFilterUpdates(): void
    {
        parent::handleTableFilterUpdates();

        $this->dispatch('appointments-table-filters-changed', filters: $this->tableFilters ?? []);
    }
}
