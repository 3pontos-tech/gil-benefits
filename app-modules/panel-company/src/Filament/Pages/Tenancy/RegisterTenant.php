<?php

namespace TresPontosTech\PanelCompany\Filament\Pages\Tenancy;

use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\TextInput;
use Filament\Pages\Tenancy\RegisterTenant as BaseRegisterTenant;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;
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
                TextInput::make('tax_id')
                    ->mask('99.999.999/9999-99')
                    ->unique('companies', 'tax_id')
                    ->required(),
            ]);
    }

    protected function handleRegistration(array $data): Company
    {
        if (isset($data['tax_id'])) {
            $cleanTaxId = preg_replace('/\D/', '', $data['tax_id']);

            if (strlen($cleanTaxId) === 14) {
                $data['tax_id'] = vsprintf('%s%s.%s%s%s.%s%s%s/%s%s%s%s-%s%s', str_split($cleanTaxId));
            }
        }

        $data['integration_access_key'] = Uuid::uuid4();
        $data['slug'] = Str::slug($data['name']);
        $data['user_id'] = auth()->user()->getKey();

        return resolve(CreateCompanyAction::class)->execute(
            CompanyDTO::make($data)
        );
    }
}
