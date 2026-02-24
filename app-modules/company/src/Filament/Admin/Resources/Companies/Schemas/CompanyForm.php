<?php

namespace TresPontosTech\Company\Filament\Admin\Resources\Companies\Schemas;

use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;
use TresPontosTech\Company\Enums\CompanyRoleEnum;

class CompanyForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('Company')
                    ->vertical()
                    ->columnSpanFull()
                    ->schema([
                        Tab::make('Basic')
                            ->schema([
                                Select::make('user_id')
                                    ->label('Owner')
                                    ->relationship('owner', 'name')
                                    ->required(),
                                TextInput::make('name')
                                    ->maxLength(255)
                                    ->live(onBlur: true, debounce: 500)
                                    ->afterStateUpdated(function (Set $set, $state): void {
                                        $set('slug', Str::slug($state));
                                    }),
                                TextInput::make('slug')
                                    ->required()
                                    ->maxLength(255),
                                TextInput::make('tax_id')
                                    ->mask('99.999.999/9999-99')
                                    ->required(),
                            ]),
                        Tab::make('Members')
                            ->schema([
                                Hidden::make('id'),
                                Repeater::make('employees')
                                    ->relationship()
                                    ->columns(2)
                                    ->schema([
                                        TextInput::make('name')
                                            ->disabled()
                                            ->required(),
                                        Select::make('role')
                                            ->options(CompanyRoleEnum::class)
                                            ->required(),
                                    ])
                                    ->saveRelationshipsUsing(function ($record, $state): void {
                                        $syncData = collect($state)
                                            ->mapWithKeys(fn ($item): array => [
                                                $item['id'] => ['role' => $item['role'],
                                                ],
                                            ])->all();
                                        $record->employees()->sync($syncData);
                                    }),
                            ]),
                    ]),

            ]);
    }
}
