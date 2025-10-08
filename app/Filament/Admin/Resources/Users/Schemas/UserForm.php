<?php

namespace App\Filament\Admin\Resources\Users\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Schema;
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
                            ->mask('999.999.999-99'),
                        TextInput::make('document_id')
                            ->mask('99.999.999-9'),
                        Select::make('company_id')
                            ->options(Company::query()->pluck('name', 'id')),
                    ])
                    ->columns(1),
            ]);
    }
}
