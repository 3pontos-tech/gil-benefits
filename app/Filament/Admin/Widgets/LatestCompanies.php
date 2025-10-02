<?php

namespace App\Filament\Admin\Widgets;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;
use TresPontosTech\Tenant\Models\Company;

class LatestCompanies extends TableWidget
{
    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 2;

    public function table(Table $table): Table
    {
        return $table
            ->searchable(false)
            ->query(fn (): Builder => Company::query()
                ->whereDate('created_at', '>=', now()->subDays(7)))
            ->columns([
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('plans.name')
                    ->badge()
                    ->default(fn (): string => 'N/A')
                    ->label('Plan'),
                TextColumn::make('tax_id')
                    ->badge()
                    ->searchable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ]);

    }
}
