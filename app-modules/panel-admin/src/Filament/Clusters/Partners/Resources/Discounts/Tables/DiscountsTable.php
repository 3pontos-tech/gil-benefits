<?php

declare(strict_types=1);

namespace TresPontosTech\PanelAdmin\Filament\Clusters\Partners\Resources\Discounts\Tables;

use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use TresPontosTech\Billing\Core\Support\MoneyCents;
use TresPontosTech\Discounts\Enums\DiscountValueTypeEnum;
use TresPontosTech\Discounts\Models\Discount;

class DiscountsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->withCount('countedRedemptions as used_redemptions_count'))
            ->columns([
                TextColumn::make('name')
                    ->label('Nome')
                    ->searchable(),

                TextColumn::make('slug')
                    ->label('Identificador'),

                TextColumn::make('discount_value')
                    ->label('Desconto')
                    ->state(fn (Discount $record): string => $record->value_type === DiscountValueTypeEnum::Percent
                        ? $record->percent_off . '%'
                        : MoneyCents::fromCents((int) $record->amount_off_cents)->format()),

                TextColumn::make('distribution')
                    ->label('Distribuição')
                    ->badge(),

                TextColumn::make('duration')
                    ->label('Duração'),

                TextColumn::make('used_redemptions_count')
                    ->label('Usos'),

                TextColumn::make('max_redemptions')
                    ->label('Limite')
                    ->placeholder('Sem limite'),

                IconColumn::make('active')
                    ->label('Ativo')
                    ->boolean(),

                TextColumn::make('grants_count')
                    ->label('Concessões')
                    ->counts('grants'),

                TextColumn::make('created_at')
                    ->label('Criado em')
                    ->dateTime('d/m/Y H:i'),
            ])
            ->recordActions([
                EditAction::make(),
            ]);
    }
}
