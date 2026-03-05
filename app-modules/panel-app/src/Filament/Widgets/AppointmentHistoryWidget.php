<?php

namespace TresPontosTech\App\Filament\Widgets;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class AppointmentHistoryWidget extends TableWidget
{
    protected static ?string $heading = 'Últimos Atendimentos';

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                auth()
                    ->user()
                    ->appointments()
                    ->latest('appointment_at')
                    ->limit(5)
                    ->getQuery()
            )
            ->columns([
                TextColumn::make('consultant.name')
                    ->label('Consultor')
                    ->placeholder('—'),

                TextColumn::make('category_type')
                    ->badge()
                    ->label('Categoria'),

                TextColumn::make('status')
                    ->badge()
                    ->label('Status'),

                TextColumn::make('appointment_at')
                    ->label('Data')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->paginated(false);
    }
}
