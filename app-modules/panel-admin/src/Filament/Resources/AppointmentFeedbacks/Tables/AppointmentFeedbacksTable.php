<?php

declare(strict_types=1);

namespace TresPontosTech\PanelAdmin\Filament\Resources\AppointmentFeedbacks\Tables;

use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use TresPontosTech\Appointments\Enums\AppointmentStatus;

class AppointmentFeedbacksTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')
                    ->label(__('panel-admin::resources.appointment_feedbacks.fields.created_at'))
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                TextColumn::make('rating')
                    ->label(__('panel-admin::resources.appointment_feedbacks.fields.rating'))
                    ->badge()
                    ->formatStateUsing(fn (int $state): string => $state . '/5')
                    ->color(fn (int $state): string => $state <= 2 ? 'danger' : ($state === 3 ? 'warning' : 'success'))
                    ->sortable(),
                TextColumn::make('comment')
                    ->label(__('panel-admin::resources.appointment_feedbacks.fields.comment'))
                    ->limit(60)
                    ->wrap()
                    ->placeholder(__('panel-admin::resources.appointment_feedbacks.fields.no_comment'))
                    ->toggleable(),
                TextColumn::make('user.name')
                    ->label(__('panel-admin::resources.appointment_feedbacks.fields.user'))
                    ->searchable(),
                TextColumn::make('appointment.consultant.name')
                    ->label(__('panel-admin::resources.appointment_feedbacks.fields.consultant'))
                    ->placeholder('—')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('appointment.company.name')
                    ->label(__('panel-admin::resources.appointment_feedbacks.fields.company'))
                    ->placeholder('—')
                    ->toggleable(),
                TextColumn::make('appointment.appointment_at')
                    ->label(__('panel-admin::resources.appointment_feedbacks.fields.appointment_at'))
                    ->dateTime('d/m/Y H:i')
                    ->toggleable(),
                TextColumn::make('appointment.status')
                    ->label(__('panel-admin::resources.appointment_feedbacks.fields.appointment_status'))
                    ->badge()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label(__('panel-admin::resources.appointment_feedbacks.fields.updated_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('rating')
                    ->label(__('panel-admin::resources.appointment_feedbacks.filters.rating'))
                    ->options(['1' => '1', '2' => '2', '3' => '3', '4' => '4', '5' => '5'])
                    ->multiple(),

                SelectFilter::make('company_id')
                    ->label(__('panel-admin::resources.appointment_feedbacks.filters.company'))
                    ->relationship('appointment.company', 'name')
                    ->searchable()
                    ->preload(false),

                Filter::make('consultant_name')
                    ->label(__('panel-admin::resources.appointment_feedbacks.filters.consultant'))
                    ->schema([
                        TextInput::make('consultant_name')
                            ->label(__('panel-admin::resources.appointment_feedbacks.filters.consultant'))
                            ->live(debounce: 500),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            $data['consultant_name'] ?? null,
                            fn (Builder $q, string $name) => $q->whereHas(
                                'appointment.consultant',
                                fn (Builder $q) => $q->where('name', 'like', sprintf('%%%s%%', $name))
                            )
                        );
                    })
                    ->indicateUsing(function (array $data): ?string {
                        return isset($data['consultant_name']) && $data['consultant_name']
                            ? __('panel-admin::resources.appointment_feedbacks.filters.consultant') . ': ' . $data['consultant_name']
                            : null;
                    }),

                Filter::make('user_name')
                    ->label(__('panel-admin::resources.appointment_feedbacks.filters.user'))
                    ->schema([
                        TextInput::make('user_name')
                            ->label(__('panel-admin::resources.appointment_feedbacks.filters.user'))
                            ->live(debounce: 500),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            $data['user_name'] ?? null,
                            fn (Builder $q, string $name) => $q->whereHas(
                                'user',
                                fn (Builder $q) => $q->where('name', 'like', sprintf('%%%s%%', $name))
                            )
                        );
                    })
                    ->indicateUsing(function (array $data): ?string {
                        return isset($data['user_name']) && $data['user_name']
                            ? __('panel-admin::resources.appointment_feedbacks.filters.user') . ': ' . $data['user_name']
                            : null;
                    }),

                Filter::make('date_range')
                    ->label(__('panel-admin::resources.appointment_feedbacks.filters.date_range'))
                    ->schema([
                        DatePicker::make('from')
                            ->label(__('panel-admin::resources.appointment_feedbacks.filters.from')),
                        DatePicker::make('until')
                            ->label(__('panel-admin::resources.appointment_feedbacks.filters.until')),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from'] ?? null,
                                fn (Builder $q, string $date) => $q->whereDate('created_at', '>=', $date)
                            )
                            ->when(
                                $data['until'] ?? null,
                                fn (Builder $q, string $date) => $q->whereDate('created_at', '<=', $date)
                            );
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];

                        if ($data['from'] ?? null) {
                            $indicators['from'] = __('panel-admin::resources.appointment_feedbacks.filters.from') . ': ' . $data['from'];
                        }

                        if ($data['until'] ?? null) {
                            $indicators['until'] = __('panel-admin::resources.appointment_feedbacks.filters.until') . ': ' . $data['until'];
                        }

                        return $indicators;
                    }),

                TernaryFilter::make('has_comment')
                    ->label(__('panel-admin::resources.appointment_feedbacks.filters.has_comment'))
                    ->trueLabel(__('panel-admin::resources.appointment_feedbacks.filters.has_comment_true'))
                    ->falseLabel(__('panel-admin::resources.appointment_feedbacks.filters.has_comment_false'))
                    ->queries(
                        true: fn (Builder $query) => $query->whereNotNull('comment')->where('comment', '!=', ''),
                        false: fn (Builder $query) => $query->where(
                            fn (Builder $q) => $q->whereNull('comment')->orWhere('comment', '')
                        ),
                    ),

                SelectFilter::make('appointment_status')
                    ->label(__('panel-admin::resources.appointment_feedbacks.filters.appointment_status'))
                    ->options(AppointmentStatus::class)
                    ->multiple()
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            filled($data['values'] ?? null),
                            fn (Builder $q) => $q->whereHas(
                                'appointment',
                                fn (Builder $q) => $q->whereIn('status', $data['values'])
                            )
                        );
                    }),
            ])
            ->persistFiltersInSession()
            ->defaultSort('created_at', 'desc')
            ->persistSortInSession()
            ->persistColumnsInSession()
            ->recordActions([
                ViewAction::make(),
            ]);
    }
}
