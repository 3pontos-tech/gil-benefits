<?php

namespace TresPontosTech\Tenant\Filament\Actions;

use Filament\Actions\CreateAction;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Illuminate\Support\Facades\Hash;
use Laravel\Cashier\Subscription;
use TresPontosTech\Company\Models\Company;

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
            fn ($record) => filament()->getTenant()->employees()->attach($record, ['role' => 'employee'])
        );

        $this->schema($this->buildFormSchema());
    }

    private function buildFormSchema(): array
    {
        return [
            Hidden::make('company_id')->default(filament()->getTenant()->getKey()),
            TextInput::make('name'),
            TextInput::make('email')
                ->rules(['email', 'unique:users,email']),
            TextInput::make('password')
                ->dehydrateStateUsing(fn ($state) => Hash::make($state))
                ->password(),
            Grid::make(1)
                ->relationship('detail')
                ->schema([
                    Hidden::make('company_id')->default(filament()->getTenant()->getKey()),
                    TextInput::make('tax_id'),
                    TextInput::make('document_id'),
                    TextInput::make('phone_number'),
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
