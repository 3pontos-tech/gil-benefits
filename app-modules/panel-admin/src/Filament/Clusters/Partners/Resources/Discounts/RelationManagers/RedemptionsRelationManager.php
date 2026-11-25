<?php

declare(strict_types=1);

namespace TresPontosTech\PanelAdmin\Filament\Clusters\Partners\Resources\Discounts\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class RedemptionsRelationManager extends RelationManager
{
    protected static string $relationship = 'redemptions';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return 'Usos';
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with('billable'))
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('billable.name')
                    ->label('Pessoa'),

                TextColumn::make('plan_slug')
                    ->label('Plano'),

                TextColumn::make('status')
                    ->label('Situação')
                    ->badge(),

                TextColumn::make('duration')
                    ->label('Duração'),

                TextColumn::make('list_amount_cents')
                    ->label('Preço cheio')
                    ->money('BRL', divideBy: 100),

                TextColumn::make('discounted_amount_cents')
                    ->label('Com desconto')
                    ->money('BRL', divideBy: 100),

                TextColumn::make('cycles_applied')
                    ->label('Cobranças aplicadas'),

                TextColumn::make('reserved_until')
                    ->label('Reservado até')
                    ->dateTime('d/m/Y H:i'),

                TextColumn::make('confirmed_at')
                    ->label('Confirmado em')
                    ->dateTime('d/m/Y H:i'),

                TextColumn::make('completed_at')
                    ->label('Concluído em')
                    ->dateTime('d/m/Y H:i'),

                TextColumn::make('created_at')
                    ->label('Criado em')
                    ->dateTime('d/m/Y H:i'),
            ]);
    }
}
