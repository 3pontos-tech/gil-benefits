<?php

namespace TresPontosTech\Admin\Filament\Widgets\Metrics;

use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\TableWidget;
use TresPontosTech\Appointments\Enums\AppointmentStatus;
use TresPontosTech\Company\Models\Company;
use TresPontosTech\Consultants\Models\Consultant;

class RankingsWidget extends TableWidget
{
    use InteractsWithPageFilters;

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 3;

    public string $activeTab = 'consultants';

    public function setTab(string $tab): void
    {
        $this->activeTab = $tab;
        $this->resetTable();
    }

    public function table(Table $table): Table
    {
        $start = $this->filters['startDate'] ? now()->parse($this->filters['startDate'])->startOfDay() : now()->subDays(30)->startOfDay();
        $end = $this->filters['endDate'] ? now()->parse($this->filters['endDate'])->endOfDay() : now()->endOfDay();

        $query = match ($this->activeTab) {
            'companies' => Company::query()
                ->withCount([
                    'appointments as total_appointments' => fn ($q) => $q->whereBetween('created_at', [$start, $end]),
                    'appointments as completed_appointments' => fn ($q) => $q->where('status', AppointmentStatus::Completed)->whereBetween('created_at', [$start, $end]),
                    'appointments as pending_appointments' => fn ($q) => $q->whereIn('status', [
                        AppointmentStatus::Pending,
                        AppointmentStatus::Scheduling,
                        AppointmentStatus::Active,
                    ])->whereBetween('created_at', [$start, $end]),
                ])
                ->whereHas('appointments', fn ($q) => $q->whereBetween('created_at', [$start, $end]))
                ->orderByDesc('total_appointments'),
            default => Consultant::query()
                ->withCount([
                    'appointments as total_appointments' => fn ($q) => $q->whereBetween('created_at', [$start, $end]),
                    'appointments as completed_appointments' => fn ($q) => $q->where('status', AppointmentStatus::Completed)->whereBetween('created_at', [$start, $end]),
                    'appointments as pending_appointments' => fn ($q) => $q->whereIn('status', [
                        AppointmentStatus::Pending,
                        AppointmentStatus::Scheduling,
                        AppointmentStatus::Active,
                    ])->whereBetween('created_at', [$start, $end]),
                ])
                ->whereHas('appointments', fn ($q) => $q->whereBetween('created_at', [$start, $end]))
                ->orderByDesc('total_appointments'),
        };

        return $table
            ->heading(__('panel-admin::widgets.metrics.rankings.heading'))
            ->searchable(false)
            ->query($query)
            ->headerActions([
                Action::make('tab_consultants')
                    ->label(__('panel-admin::widgets.metrics.rankings.tab_consultants'))
                    ->color($this->activeTab === 'consultants' ? 'primary' : 'gray')
                    ->action(fn () => $this->setTab('consultants')),
                Action::make('tab_companies')
                    ->label(__('panel-admin::widgets.metrics.rankings.tab_companies'))
                    ->color($this->activeTab === 'companies' ? 'primary' : 'gray')
                    ->action(fn () => $this->setTab('companies')),
            ])
            ->columns([
                TextColumn::make('name')
                    ->label($this->activeTab === 'companies'
                        ? __('panel-admin::widgets.metrics.rankings.column_company')
                        : __('panel-admin::widgets.metrics.rankings.column_consultant'))
                    ->searchable(),
                TextColumn::make('total_appointments')
                    ->label(__('panel-admin::widgets.metrics.rankings.column_total'))
                    ->sortable(),
                TextColumn::make('completed_appointments')
                    ->label(__('panel-admin::widgets.metrics.rankings.column_completed'))
                    ->sortable(),
                TextColumn::make('pending_appointments')
                    ->label(__('panel-admin::widgets.metrics.rankings.column_pending'))
                    ->sortable(),
                TextColumn::make('completion_rate')
                    ->label(__('panel-admin::widgets.metrics.rankings.column_completion_rate'))
                    ->state(fn ($record): string => $record->total_appointments > 0
                        ? round(($record->completed_appointments / $record->total_appointments) * 100, 1) . '%'
                        : '0%'),
            ]);
    }
}
