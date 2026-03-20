<?php

namespace TresPontosTech\PanelCompany\Filament\Widgets;

use Filament\Facades\Filament;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;
use TresPontosTech\Appointments\Models\Appointment;

class LatestScheduledSessionsTableWidget extends TableWidget
{
    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 20;

    public function table(Table $table): Table
    {
        return $table
            ->searchable(true)
            ->heading(__('panel-company::widgets.latest_sessions.heading'))
            ->query(fn (): Builder => Appointment::query()
                ->where('company_id', Filament::getTenant()->id)->latest())
            ->columns([
                TextColumn::make('consultant.name')
                    ->label(__('panel-company::widgets.latest_sessions.consultant'))
                    ->searchable(),
                TextColumn::make('user.name')
                    ->label(__('panel-company::widgets.latest_sessions.employee'))
                    ->searchable(),
                TextColumn::make('category_type')
                    ->label(__('panel-company::widgets.latest_sessions.category'))
                    ->badge()
                    ->searchable(),
                TextColumn::make('appointment_at')
                    ->label(__('panel-company::widgets.latest_sessions.appointment_date'))
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->searchable(),
                TextColumn::make('created_at')
                    ->label(__('panel-company::widgets.latest_sessions.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label(__('panel-company::widgets.latest_sessions.updated_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('company.name')
                    ->label(__('panel-company::widgets.latest_sessions.company'))
                    ->searchable(),
                TextColumn::make('external_opportunity_id')
                    ->label(__('panel-company::widgets.latest_sessions.external_opportunity_id'))
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('external_appointment_id')
                    ->label(__('panel-company::widgets.latest_sessions.external_appointment_id'))
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ]);

    }
}
