<?php

namespace App\Filament\Admin\Resources\Users\Schemas;

use App\Models\Companies\Company;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Schema;

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
                        TextInput::make('tax_id'),
                        TextInput::make('document_id'),
                        Select::make('company_id')
                            ->options(Company::query()->pluck('name', 'id')),
                    ])
                    ->columns(1),
            ]);
    }
}
