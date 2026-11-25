<?php

declare(strict_types=1);

namespace TresPontosTech\PanelAdmin\Filament\Clusters\Partners\Resources\Discounts\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use TresPontosTech\Billing\Core\Enums\PriceAudienceEnum;

class DiscountForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Nome')
                    ->required()
                    ->maxLength(255),

                TextInput::make('slug')
                    ->label('Identificador')
                    ->required()
                    ->alphaDash()
                    ->unique(ignoreRecord: true)
                    ->disabled(fn (string $operation): bool => $operation === 'edit')
                    ->dehydrated(fn (string $operation): bool => $operation === 'create'),

                TextInput::make('percent_off')
                    ->label('Desconto')
                    ->numeric()
                    ->minValue(1)
                    ->maxValue(100)
                    ->suffix('%')
                    ->required(),

                Select::make('audience')
                    ->label('Público')
                    ->options([
                        PriceAudienceEnum::Standalone->value => 'Pessoa física (plano pessoal)',
                    ])
                    ->default(PriceAudienceEnum::Standalone->value)
                    ->required(),

                TextInput::make('claim_window_days')
                    ->label('Prazo para assinar')
                    ->numeric()
                    ->minValue(1)
                    ->suffix('dias')
                    ->helperText('Contado a partir da reserva.')
                    ->default(180)
                    ->required(),

                Toggle::make('active')
                    ->label('Ativo'),
            ]);
    }
}
