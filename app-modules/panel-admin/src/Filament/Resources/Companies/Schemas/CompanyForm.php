<?php

namespace TresPontosTech\Admin\Filament\Resources\Companies\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class CompanyForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('user_id')
                    ->label(__('panel-admin::resources.companies.form.owner'))
                    ->relationship('owner', 'name')
                    ->required(),
                TextInput::make('name')
                    ->maxLength(255)
                    ->live(onBlur: true, debounce: 500)
                    ->afterStateUpdated(function (Set $set, string $state): void {
                        $slug = sprintf('%s-%s', $state, Str::random(4));
                        $set('slug', str($slug)->slug());
                    }),
                TextInput::make('slug')
                    ->required()
                    ->readOnly()
                    ->maxLength(255)
                    ->unique('companies', 'slug'),
                TextInput::make('tax_id')
                    ->mask('99.999.999/9999-99')
                    ->required(),
            ]);
    }
}
