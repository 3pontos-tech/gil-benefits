<?php

declare(strict_types=1);

namespace TresPontosTech\PanelCompany\Filament\Widgets\Metrics;

use Filament\Facades\Filament;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;
use TresPontosTech\Billing\Core\Enums\UserCreditStatusEnum;
use TresPontosTech\Billing\Core\Models\UserCredit;

class CreditUsageTableWidget extends TableWidget
{
    use InteractsWithPageFilters;

    protected static bool $isDiscovered = false;

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 1;

    public function table(Table $table): Table
    {
        return $table
            ->heading(__('panel-company::widgets.credit_usage.heading'))
            ->query($this->tableQuery())
            ->columns([
                TextColumn::make('holder.name')
                    ->label(__('panel-company::widgets.credit_usage.employee'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('appointment.appointment_at')
                    ->label(__('panel-company::widgets.credit_usage.appointment_date'))
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),

                TextColumn::make('appointment.consultant.name')
                    ->label(__('panel-company::widgets.credit_usage.consultant'))
                    ->searchable()
                    ->default('—'),

                TextColumn::make('appointment.category_type')
                    ->label(__('panel-company::widgets.credit_usage.category'))
                    ->formatStateUsing(fn ($state) => $state?->getLabel() ?? '—'),

                TextColumn::make('status')
                    ->label(__('panel-company::widgets.credit_usage.status'))
                    ->badge()
                    ->color(fn (UserCreditStatusEnum $state): string|array => $state->getColor()),

                TextColumn::make('transferred_at')
                    ->label(__('panel-company::widgets.credit_usage.transferred_at'))
                    ->dateTime('d/m/Y')
                    ->sortable()
                    ->default('—'),
            ]);
    }

    private function tableQuery(): Builder
    {
        ['start' => $start, 'end' => $end] = $this->dateRange();

        $tenantId = Filament::getTenant()->id;
        $userId = data_get($this->filters, 'userId');

        return UserCredit::query()
            ->with(['holder', 'appointment.consultant'])
            ->join('appointments', 'appointments.id', '=', 'user_credits.appointment_id')
            ->where('user_credits.company_id', $tenantId)
            ->whereIn('user_credits.status', [UserCreditStatusEnum::Used, UserCreditStatusEnum::InUse])
            ->whereBetween('appointments.appointment_at', [$start, $end])
            ->when($userId, fn ($q) => $q->where('user_credits.holder_id', $userId))
            ->select('user_credits.*')
            ->latest('appointments.appointment_at');
    }

    private function dateRange(): array
    {
        $startDate = data_get($this->filters, 'startDate');
        $endDate = data_get($this->filters, 'endDate');

        return [
            'start' => filled($startDate) ? now()->parse($startDate)->startOfDay() : now()->subDays(30)->startOfDay(),
            'end' => filled($endDate) ? now()->parse($endDate)->endOfDay() : now()->endOfDay(),
        ];
    }
}
