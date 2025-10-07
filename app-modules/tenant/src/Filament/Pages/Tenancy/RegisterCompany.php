<?php

namespace TresPontosTech\Tenant\Filament\Pages\Tenancy;

use Filament\Forms\Components\TextInput;
use Filament\Pages\Tenancy\RegisterTenant;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;
use TresPontosTech\Company\Models\Company;

class RegisterCompany extends RegisterTenant
{
    public static function getLabel(): string
    {
        return 'Register team';
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
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
                    ->mask('99.999.999/9999-99'),
            ]);
    }

    protected function handleRegistration(array $data): Company
    {
        $user = auth()->user();
        $company = $user->ownedCompanies()->create($data);
        $user->companies()->attach($company);

        return $company;
    }
}
