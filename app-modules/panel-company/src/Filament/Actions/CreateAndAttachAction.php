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
                filament()->getTenant()->employees()->attach($record);
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
                        ->label('Nome')
                        ->required(),
                    TextInput::make('email')
                        ->rules(['email', 'unique:users,email'])
                        ->email()
                        ->required(),
                    TextInput::make('password')
                        ->label('Senha')
                        ->dehydrateStateUsing(fn ($state) => Hash::make($state))
                        ->password()
                        ->required(),
                ]),
            Grid::make(2),
            Fieldset::make('Detalhes')
                ->relationship('detail')
                ->schema([
                    Hidden::make('company_id')->default(filament()->getTenant()->getKey()),
                    TextInput::make('tax_id')
                        ->label('CPF')
                        ->mask('999.999.999-99')
                        ->rule(new UniqueAtCompany)
                        ->required(),
                    TextInput::make('document_id')
                        ->label('RG')
                        ->mask('99.999.999-9')
                        ->rule(new UniqueAtCompany)
                        ->required(),
                    PhoneInput::make('phone_number')
                        ->label('Telefone')
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

        /** @var Subscription $activeSubscription */
        $activeSubscription = $tenant->subscriptions()->where('stripe_status', '=', 'active')->first();

        $employeesCount = $tenant->employees()->wherePivot('active', true)->count();

        return $activeSubscription->quantity <= $employeesCount;
    }
}
