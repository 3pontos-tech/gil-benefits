<?php

namespace TresPontosTech\PanelCompany\Filament\Pages\Tenancy;

use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\TextInput;
use Filament\Pages\Tenancy\RegisterTenant as BaseRegisterTenant;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;
use Leandrocfe\FilamentPtbrFormFields\Document;
use Ramsey\Uuid\Uuid;
use TresPontosTech\Company\Actions\CreateCompanyAction;
use TresPontosTech\Company\DTOs\CompanyDTO;
use TresPontosTech\Company\Models\Company;

class RegisterTenant extends BaseRegisterTenant
{
    public static function canView(): bool
    {
        /** @var Company $tenant */
        $tenant = filament()->getTenant();

        if (is_null($tenant)) {
            return true;
        }

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
                    ->required(),
                Hidden::make('slug'),
                Document::make('tax_id')
                    ->dehydrateMask()
                    ->label(__('panel-admin::resources.companies.form.tax_id'))
                    ->cnpj()
                    ->unique('companies', 'tax_id')
                    ->required(),
            ]);
    }

    protected function handleRegistration(array $data): Company
    {
        $data['integration_access_key'] = Uuid::uuid4();
        $data['slug'] = Str::slug($data['name']);
        $data['user_id'] = auth()->user()->getKey();

        return resolve(CreateCompanyAction::class)->execute(
            CompanyDTO::make($data)
        );
    }
}
