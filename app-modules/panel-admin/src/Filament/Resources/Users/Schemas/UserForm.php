<?php

namespace TresPontosTech\Admin\Filament\Resources\Users\Schemas;

use App\Filament\Shared\Fields\DocumentIdInput;
use App\Filament\Shared\Fields\TaxIdInput;
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
                        TaxIdInput::make()
                            ->label(__('panel-admin::resources.users.form.tax_id'))
                            ->unique(),
                        DocumentIdInput::make()
                            ->label(__('panel-admin::resources.users.form.document_id'))
                            ->unique(),
                        Select::make('company_id')
                            ->label(__('panel-admin::resources.users.form.company'))
                            ->options(Company::query()->pluck('name', 'id')),
                    ])
                    ->columns(1),
            ]);
    }
}
