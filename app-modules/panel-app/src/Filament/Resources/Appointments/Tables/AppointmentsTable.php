<?php

namespace TresPontosTech\App\Filament\Resources\Appointments\Tables;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class AppointmentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->where('user_id', auth()->user()->getKey()))
            ->columns([
                TextColumn::make('consultant.name')
                    ->label(__('appointments::resources.appointments.table.columns.consultant'))
                    ->placeholder('—')
                    ->searchable(),
                TextColumn::make('category_type')
                    ->label(__('panel-app::resources.appointments.table.category_type'))
                    ->badge()
                    ->searchable(),
                TextColumn::make('appointment_at')
                    ->label(__('appointments::resources.appointments.table.columns.appointment_at'))
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('status')
                    ->label(__('appointments::resources.appointments.table.columns.status'))
                    ->badge()
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
            ->defaultSort('appointment_at', 'desc')
            ->filters([
                //
            ])
            ->recordActions([

            ]);

    }
}
