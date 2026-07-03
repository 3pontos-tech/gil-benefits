<?php

namespace TresPontosTech\PanelCompany\Filament\Actions;

use App\Filament\Shared\Fields\DocumentIdInput;
use App\Filament\Shared\Fields\TaxIdInput;
use App\Models\Users\User;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Grid;
use TresPontosTech\Billing\Core\Models\CompanyPlan;
use TresPontosTech\Billing\Core\Models\Subscriptions\Subscription;
use TresPontosTech\Company\Models\Company;
use TresPontosTech\PanelCompany\Rules\UniqueAtCompany;
use TresPontosTech\Permissions\Roles;
use TresPontosTech\User\Events\UserRegistered;
use Ysfkaya\FilamentPhoneInput\Forms\PhoneInput;

class CreateAndAttachAction extends CreateAction
{
    private ?string $plainPassword = null;

    public static function getDefaultName(): ?string
    {
        return 'create-and-attach-tenant-employee';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->disabled(fn (): bool => $this->isSubscriptionCapacityExceeded());

        $this->before(function (): void {
            if ($this->isSubscriptionCapacityExceeded()) {
                Notification::make()
                    ->danger()
                    ->title('Limite atingido')
                    ->body('Não há mais assentos disponíveis no seu plano.')
                    ->send();

                $this->halt();
            }
        });

        $this->mutateFormDataUsing(function (array $data): array {
            $this->plainPassword = $data['password'] ?? null;

            return $data;
        });

        $this->after(
            function (User $record): void {
                /** @var Company $tenant */
                $tenant = filament()->getTenant();
                $tenant->employees()->syncWithoutDetaching($record);
                $record->assignRole(Roles::Employee);
                event(new UserRegistered($record, Roles::Employee, $this->plainPassword));
            }
        );

        $this->schema($this->buildFormSchema());

    }

    /**
     * @return array<Component>
     */
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
                        ->password()
                        ->required(),
                ]),
            Grid::make(2),
            Fieldset::make(__('panel-company::resources.actions.create_and_attach.details'))
                ->relationship('detail')
                ->schema([
                    Hidden::make('company_id')->default(filament()->getTenant()->getKey()),
                    TaxIdInput::make()
                        ->label(__('panel-company::resources.actions.create_and_attach.cpf'))
                        ->rule(new UniqueAtCompany),
                    DocumentIdInput::make()
                        ->rule(new UniqueAtCompany),
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
