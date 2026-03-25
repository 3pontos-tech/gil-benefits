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
                Fieldset::make(__('panel-admin::resources.users.form.fieldset_user'))
                    ->schema([
                        TextInput::make('name')
                            ->label(__('panel-admin::resources.users.form.name'))
                            ->required(),
                        TextInput::make('email')
                            ->label(__('panel-admin::resources.users.form.email'))
                            ->email()
                            ->unique()
                            ->required(),
                        TextInput::make('password')
                            ->label(__('panel-admin::resources.users.form.password'))
                            ->password()
                            ->required(fn ($operation): bool => $operation === 'create')
                            ->dehydrated(fn ($state): bool => filled($state)),
                    ])
                    ->columns(1),
                Fieldset::make(__('panel-admin::resources.users.form.fieldset_details'))
                    ->relationship('detail')
                    ->schema([
                        TextInput::make('tax_id')
                            ->label(__('panel-admin::resources.users.form.tax_id'))
                            ->mask('999.999.999-99')
                            ->required()
                            ->unique(),
                        TextInput::make('document_id')
                            ->label(__('panel-admin::resources.users.form.document_id'))
                            ->mask(RawJs::make(<<<'JS'
                                $input.replace(/[^a-zA-Z0-9]/g, '').length > 9
                                    ? '***.***.***-**'
                                    : '**.***.***-*'
                            JS))
                            ->minLength(5)
                            ->maxLength(14)
                            ->unique(),
                        Select::make('company_id')
                            ->label(__('panel-admin::resources.users.form.company'))
                            ->options(Company::query()->pluck('name', 'id')),
                    ])
                    ->columns(1),
            ]);
    }
}
