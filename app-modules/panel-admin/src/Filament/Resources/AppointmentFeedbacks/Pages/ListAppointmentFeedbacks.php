<?php

declare(strict_types=1);

namespace TresPontosTech\PanelAdmin\Filament\Resources\AppointmentFeedbacks\Pages;

use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;
use Symfony\Component\HttpFoundation\StreamedResponse;
use TresPontosTech\PanelAdmin\Actions\AppointmentFeedbacks\ExportAppointmentFeedbacksCsv;
use TresPontosTech\PanelAdmin\Filament\Resources\AppointmentFeedbacks\AppointmentFeedbackResource;
use TresPontosTech\PanelAdmin\Filament\Widgets\AppointmentFeedbacksStatsOverview;

class ListAppointmentFeedbacks extends ListRecords
{
    protected static string $resource = AppointmentFeedbackResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('exportCsv')
                ->label(__('panel-admin::resources.appointment_feedbacks.actions.export_csv'))
                ->icon(Heroicon::ArrowDownTray)
                ->color('gray')
                ->action(fn (): StreamedResponse => resolve(ExportAppointmentFeedbacksCsv::class)->handle(
                    $this->getFilteredSortedTableQuery() ?? AppointmentFeedbackResource::getEloquentQuery()
                )),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            AppointmentFeedbacksStatsOverview::class,
        ];
    }

    public function mount(): void
    {
        parent::mount();

        $persisted = session($this->getTableFiltersSessionKey());

        if (filled($persisted)) {
            $this->dispatch('appointment-feedbacks-table-filters-changed', filters: $persisted);
        }
    }

    protected function handleTableFilterUpdates(): void
    {
        parent::handleTableFilterUpdates();

        $this->dispatch('appointment-feedbacks-table-filters-changed', filters: $this->tableFilters ?? []);
    }
}
