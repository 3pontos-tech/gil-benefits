<?php

namespace TresPontosTech\Vouchers\Filament\Company\Resources\Vouchers\Tables;

use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class VouchersTable
{
    public static function configure(Table $table): Table
    {
        return $table

            ->recordActions([
                ViewAction::make(),
            ])
            ->columns([
                TextColumn::make('code')
                    ->copyable()
                    ->limit(6)
                    ->searchable(),
                TextColumn::make('status')
                    ->badge()
                    ->searchable(),
                TextColumn::make('consultant.name')
                    ->numeric()
                    ->default('N/D')
                    ->sortable(),
                TextColumn::make('user.name')
                    ->numeric()
                    ->default('N/D')
                    ->sortable(),

                TextColumn::make('valid_until')
                    ->dateTime()
                    ->sortable(),
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
