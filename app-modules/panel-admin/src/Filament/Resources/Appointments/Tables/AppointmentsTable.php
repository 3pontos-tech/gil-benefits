<?php

namespace TresPontosTech\Admin\Filament\Resources\Appointments\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use TresPontosTech\Appointments\Enums\AppointmentStatus;

class AppointmentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('consultant.name')
                    ->label(__('appointments::resources.appointments.table.columns.consultant'))
                    ->searchable(),
                TextColumn::make('user.name')
                    ->label(__('appointments::resources.appointments.table.columns.user'))
                    ->searchable(),
                TextColumn::make('appointment_at')
                    ->label(__('appointments::resources.appointments.table.columns.appointment_at'))
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                TextColumn::make('status')
                    ->label(__('appointments::resources.appointments.table.columns.status'))
                    ->searchable(),
                TextColumn::make('created_at')
                    ->label(__('appointments::resources.appointments.table.columns.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label(__('appointments::resources.appointments.table.columns.updated_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Filter::make('date_range')
                    ->label('Data')
                    ->schema([
                        DatePicker::make('from')
                            ->label(__('appointments::resources.appointments.table.columns.from')),
                        DatePicker::make('until')
                            ->label(__('appointments::resources.appointments.table.columns.until')),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from'] ?? null,
                                fn (Builder $q, string $date) => $q->whereDate('appointment_at', '>=', $date)
                            )
                            ->when(
                                $data['until'] ?? null,
                                fn (Builder $q, string $date) => $q->whereDate('appointment_at', '<=', $date)
                            );
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];

                        if ($data['from'] ?? null) {
                            $indicators['from'] = __('appointments::resources.appointments.table.columns.from') . ': ' . $data['from'];
                        }

                        if ($data['until'] ?? null) {
                            $indicators['until'] = __('appointments::resources.appointments.table.columns.until') . ': ' . $data['until'];
                        }

                        return $indicators;
                    }),

                Filter::make('user_name')
                    ->label(__('appointments::resources.appointments.table.columns.user'))
                    ->schema([
                        TextInput::make('user_name')
                            ->label(__('appointments::resources.appointments.table.columns.user'))
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
                            ? __('appointments::resources.appointments.table.columns.user') . ': ' . $data['user_name']
                            : null;
                    }),

                Filter::make('consultant_name')
                    ->label(__('appointments::resources.appointments.table.columns.consultant'))
                    ->schema([
                        TextInput::make('consultant_name')
                            ->label(__('appointments::resources.appointments.table.columns.consultant'))
                            ->live(debounce: 500),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            $data['consultant_name'] ?? null,
                            fn (Builder $q, string $name) => $q->whereHas(
                                'consultant',
                                fn (Builder $q) => $q->where('name', 'like', sprintf('%%%s%%', $name))
                            )
                        );
                    })
                    ->indicateUsing(function (array $data): ?string {
                        return isset($data['consultant_name']) && $data['consultant_name']
                            ? __('appointments::resources.appointments.table.columns.consultant') . ': ' . $data['consultant_name']
                            : null;
                    }),

                SelectFilter::make('status')
                    ->label('Status')
                    ->options(AppointmentStatus::class)
                    ->multiple(),

                SelectFilter::make('company_id')
                    ->label(__('appointments::resources.appointments.table.columns.company'))
                    ->relationship('company', 'name')
                    ->searchable()
                    ->preload(false),
            ])
            ->persistFiltersInSession()
            ->defaultSort('appointment_at', 'desc')
            ->persistSortInSession()
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
