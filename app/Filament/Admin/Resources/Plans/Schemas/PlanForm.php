<?php

namespace App\Filament\Admin\Resources\Plans\Schemas;

use App\Enums\PlanTypeEnum;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class PlanForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required(),
                TextInput::make('price')
                    ->required()
                    ->numeric()
                    ->prefix('R$'),
                Select::make('type')
                    ->options(PlanTypeEnum::class)
                    ->required(),
                TextInput::make('hours_included')
                    ->required()
                    ->numeric(),
                Textarea::make('description')
                    ->required()
                    ->columnSpanFull(),
                Repeater::make('Items')
                    ->label('Plan Items')
                    ->relationship('items')
                    ->schema([
                        TextInput::make('name')
                            ->label('Item Name')
                            ->required(),
                        TextInput::make('price')
                            ->label('Item Price')
                            ->numeric()
                            ->required()
                            ->prefix('R$'),
                        TextInput::make('type')
                            ->label('Item Type'),
                        TextInput::make('quantity')
                            ->label('Quantity')
                            ->numeric()
                            ->required(),
                    ])
                    ->columns(2)
                    ->columnSpan('full'),
            ]);
    }
}
