<?php

namespace TresPontosTech\PanelCompany\Filament\Actions;

use App\Models\Users\User;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Grid;
use Illuminate\Support\Facades\Hash;
use Laravel\Cashier\Subscription;
use TresPontosTech\Billing\Core\Models\CompanyPlan;
use TresPontosTech\Company\Models\Company;
use TresPontosTech\PanelCompany\Rules\UniqueAtCompany;
use TresPontosTech\Permissions\Roles;
use Ysfkaya\FilamentPhoneInput\Forms\PhoneInput;

class CreateAndAttachAction extends CreateAction
{
    public static function getDefaultName(): ?string
    {
        return 'create-and-attach-tenant-employee';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->disabled(fn (): bool => $this->isSubscriptionCapacityExceeded());

        $this->after(
            function (User $record): void {
                filament()->getTenant()->employees()->syncWithoutDetaching($record);
                $record->assignRole(Roles::Employee);
            }
        );

        $this->schema($this->buildFormSchema());
    }

    private function buildFormSchema(): array
    {
        return [
            Hidden::make('company_id')->default(filament()->getTenant()->getKey()),
            Grid::make(2)
                ->schema([
                    TextInput::make('name')
                        ->label(__('panel-company::resources.actions.create_and_attach.name'))
                        ->required(),
                    TextInput::make('email')
                        ->rules(['email', 'unique:users,email'])
                        ->email()
                        ->required(),
                    TextInput::make('password')
                        ->label(__('panel-company::resources.actions.create_and_attach.password'))
                        ->dehydrateStateUsing(fn ($state) => Hash::make($state))
                        ->password()
                        ->required(),
                ]),
            Grid::make(2),
            Fieldset::make(__('panel-company::resources.actions.create_and_attach.details'))
                ->relationship('detail')
                ->schema([
                    Hidden::make('company_id')->default(filament()->getTenant()->getKey()),
                    TextInput::make('tax_id')
                        ->label(__('panel-company::resources.actions.create_and_attach.cpf'))
                        ->mask('999.999.999-99')
                        ->rule(new UniqueAtCompany)
                        ->required(),
                    TextInput::make('document_id')
                        ->label(__('panel-company::resources.actions.create_and_attach.rg'))
                        ->mask('99.999.999-9')
                        ->rule(new UniqueAtCompany)
                        ->required(),
                    PhoneInput::make('phone_number')
                        ->label(__('panel-company::resources.actions.create_and_attach.phone'))
                        ->defaultCountry('BR')
                        ->initialCountry('BR')
                        ->disableLookup()
                        ->strictMode()
                        ->required(),
                ]),
        ];
    }

    private function isSubscriptionCapacityExceeded(): bool
    {
        /** @var Company $tenant */
        $tenant = filament()->getTenant();

        $employeesCount = $tenant->employees()->wherePivot('active', true)->count();

        /** @var CompanyPlan|null $contractualPlan */
        $contractualPlan = $tenant->activeContractualPlan();

        if ($contractualPlan !== null) {
            return $contractualPlan->seats <= $employeesCount;
        }

        /** @var Subscription $activeSubscription */
        $activeSubscription = $tenant->subscriptions()->where('stripe_status', '=', 'active')->first();

        return $activeSubscription->quantity <= $employeesCount;
    }
}
