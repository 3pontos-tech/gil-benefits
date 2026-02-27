<?php

namespace TresPontosTech\Tenant\Filament\Pages\Tenancy;

use Filament\Forms\Components\TextInput;
use Filament\Pages\Tenancy\RegisterTenant as BaseRegisterTenant;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;
use Ramsey\Uuid\Uuid;
use TresPontosTech\Company\Models\Company;
use TresPontosTech\Permissions\Roles;

class RegisterTenant extends BaseRegisterTenant
{
    public static function canView(): bool
    {
        /** @var Company $tenant */
        $tenant = filament()->getTenant();

        return ! $tenant->subscribed('company');
    }

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
        $data['integration_access_key'] = Uuid::uuid4();
        $user = auth()->user();
        $company = $user->ownedCompanies()->create($data);
        $user->companies()->attach($company);
        $user->assignRole(Roles::CompanyOwner);

        return $company;
    }
}
