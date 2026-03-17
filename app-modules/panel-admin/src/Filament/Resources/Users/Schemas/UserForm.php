<?php

namespace TresPontosTech\Admin\Filament\Resources\Users\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Schema;
use Filament\Support\RawJs;
use TresPontosTech\Company\Models\Company;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Fieldset::make('User')
                    ->schema([
                        TextInput::make('name')
                            ->required(),
                        TextInput::make('email')
                            ->email()
                            ->unique()
                            ->required(),
                        TextInput::make('password')
                            ->password()
                            ->required(),
                    ])
                    ->columns(1),
                Fieldset::make('Details')
                    ->relationship('detail')
                    ->schema([
                        TextInput::make('tax_id')
                            ->mask('999.999.999-99')
                            ->required()
                            ->unique(),
                        TextInput::make('document_id')
                            ->mask(RawJs::make(<<<'JS'
                                $input.replace(/\D/g, '').length > 9
                                    ? '999.999.999-99'
                                    : '99.999.999-9'
                            JS))
                            ->minLength(7)
                            ->maxLength(14)
                            ->required()
                            ->unique(),
                        Select::make('company_id')
                            ->options(Company::query()->pluck('name', 'id')),
                    ])
                    ->columns(1),
            ]);
    }
}
