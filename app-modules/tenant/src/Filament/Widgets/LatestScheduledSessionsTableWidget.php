<?php

namespace TresPontosTech\Tenant\Filament\Widgets;

use Filament\Actions\BulkActionGroup;
use Filament\Facades\Filament;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;
use TresPontosTech\Appointments\Models\Appointment;

class LatestScheduledSessionsTableWidget extends TableWidget
{
    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->searchable(true)
            ->heading('Últimas consultorias agendadas')
            ->query(fn (): Builder => Appointment::query()
                ->where('company_id', Filament::getTenant()->id)->latest())
            ->columns([
                TextColumn::make('consultant.name')
                    ->label('Consultor')
                    ->searchable(),
                TextColumn::make('user.name')
                    ->label('Funcionário')
                    ->searchable(),
                TextColumn::make('category_type')
                    ->label('Categoria')
                    ->badge()
                    ->searchable(),
                TextColumn::make('appointment_at')
                    ->label('Data da consultoria')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->searchable(),
                TextColumn::make('created_at')
                    ->label('Criado em')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label('Atualizado em')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('company.name')
                    ->label('Empresa')
                    ->searchable(),
                TextColumn::make('external_opportunity_id')
                    ->label('ID da oportunidade externa')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('external_appointment_id')
                    ->label('ID da consultoria externa')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ]);

    }
}
