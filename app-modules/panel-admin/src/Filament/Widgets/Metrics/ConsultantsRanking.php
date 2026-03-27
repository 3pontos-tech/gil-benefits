<?php

namespace TresPontosTech\Admin\Filament\Widgets\Metrics;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\TableWidget;
use TresPontosTech\Appointments\Enums\AppointmentStatus;
use TresPontosTech\Consultants\Models\Consultant;

class ConsultantsRanking extends TableWidget
{
    use InteractsWithPageFilters;

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 3;

    public function table(Table $table): Table
    {
        $start = $this->filters['startDate'] ? now()->parse($this->filters['startDate'])->startOfDay() : now()->subDays(30)->startOfDay();
        $end = $this->filters['endDate'] ? now()->parse($this->filters['endDate'])->endOfDay() : now()->endOfDay();

        return $table
            ->heading(__('panel-admin::widgets.metrics.consultants_ranking.heading'))
            ->searchable(false)
            ->query(
                Consultant::query()
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
                    ->orderByDesc('total_appointments')
            )
            ->columns([
                TextColumn::make('name')
                    ->label(__('panel-admin::widgets.metrics.consultants_ranking.column_consultant'))
                    ->searchable(),
                TextColumn::make('total_appointments')
                    ->label(__('panel-admin::widgets.metrics.consultants_ranking.column_total'))
                    ->sortable(),
                TextColumn::make('completed_appointments')
                    ->label(__('panel-admin::widgets.metrics.consultants_ranking.column_completed'))
                    ->sortable(),
                TextColumn::make('pending_appointments')
                    ->label(__('panel-admin::widgets.metrics.consultants_ranking.column_pending'))
                    ->sortable(),
                TextColumn::make('completion_rate')
                    ->label(__('panel-admin::widgets.metrics.consultants_ranking.column_completion_rate'))
                    ->state(fn (Consultant $record): string => $record->total_appointments > 0
                        ? round(($record->completed_appointments / $record->total_appointments) * 100, 1) . '%'
                        : '0%'
                    ),
            ]);
    }
}
