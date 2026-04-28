<?php

namespace TresPontosTech\Admin\Filament\Widgets\Metrics;

use Carbon\CarbonInterface;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;
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
        $startDate = data_get($this->filters, 'startDate');
        $endDate = data_get($this->filters, 'endDate');

        $start = filled($startDate) ? now()->parse($startDate)->startOfDay() : now()->subDays(30)->startOfDay();
        $end = filled($endDate) ? now()->parse($endDate)->endOfDay() : now()->endOfDay();

        $model = $this->activeTab === 'companies' ? Company::class : Consultant::class;

        return $table
            ->heading(__('panel-admin::widgets.metrics.rankings.heading'))
            ->searchable(false)
            ->query($this->rankingQuery($model, $start, $end))
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
                TextColumn::make('feedbacks_avg_rating')
                    ->label(__('panel-admin::widgets.metrics.rankings.column_avg_rating'))
                    ->state(fn ($record): string => filled($record->feedbacks_avg_rating)
                        ? round((float) $record->feedbacks_avg_rating, 1) . '/5'
                        : '—'
                    )
                    ->sortable(),
            ]);
    }

    /**
     * @param  class-string<Company|Consultant>  $model
     */
    private function rankingQuery(string $model, CarbonInterface $start, CarbonInterface $end): Builder
    {
        return $model::query()
            ->withCount([
                'appointments as total_appointments' => fn ($query) => $query->whereBetween('created_at', [$start, $end]),
                'appointments as completed_appointments' => fn ($query) => $query->where('status', AppointmentStatus::Completed)->whereBetween('created_at', [$start, $end]),
                'appointments as pending_appointments' => fn ($query) => $query->whereIn('status', [
                    AppointmentStatus::Pending,
                    AppointmentStatus::Active,
                ])->whereBetween('created_at', [$start, $end]),
            ])
            ->withAvg(['feedbacks' => fn ($query) => $query->whereBetween('appointment_feedbacks.created_at', [$start, $end])], 'rating')
            ->whereHas('appointments', fn ($query) => $query->whereBetween('created_at', [$start, $end]))
            ->orderByRaw('feedbacks_avg_rating DESC NULLS LAST')
            ->orderByDesc('completed_appointments');
    }
}
