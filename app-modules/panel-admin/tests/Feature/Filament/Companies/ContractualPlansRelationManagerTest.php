<?php

declare(strict_types=1);

use Filament\Actions\Testing\TestAction;
use TresPontosTech\Billing\Core\Enums\BillableTypeEnum;
use TresPontosTech\Billing\Core\Enums\BillingProviderEnum;
use TresPontosTech\Billing\Core\Enums\CompanyPlanStatusEnum;
use TresPontosTech\Billing\Core\Models\Plan;
use TresPontosTech\Company\Models\Company;
use TresPontosTech\PanelAdmin\Filament\Resources\Companies\Pages\EditCompany;
use TresPontosTech\PanelAdmin\Filament\Resources\Companies\RelationManagers\ContractualPlansRelationManager;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Livewire\livewire;

beforeEach(function (): void {
    actingAsAdmin();

    $this->company = Company::factory()->create();

    $this->plan = Plan::factory()->createOne([
        'provider' => BillingProviderEnum::Contractual->value,
        'type' => BillableTypeEnum::Company->value,
        'active' => true,
    ]);
});

function contractualPlansManager(): object
{
    return livewire(ContractualPlansRelationManager::class, [
        'ownerRecord' => test()->company,
        'pageClass' => EditCompany::class,
    ]);
}

it('refuses a contractual plan without a start date', function (): void {
    contractualPlansManager()
        ->assertOk()
        ->callAction(TestAction::make('create')->table(), data: [
            'plan_id' => $this->plan->getKey(),
            'seats' => 10,
            'monthly_appointments_per_employee' => 1,
            'status' => CompanyPlanStatusEnum::Active->value,
            'starts_at' => null,
        ])
        ->assertHasActionErrors(['starts_at' => 'required']);
});

it('creates the contractual plan when the start date is given', function (): void {
    contractualPlansManager()
        ->assertOk()
        ->callAction(TestAction::make('create')->table(), data: [
            'plan_id' => $this->plan->getKey(),
            'seats' => 10,
            'monthly_appointments_per_employee' => 1,
            'status' => CompanyPlanStatusEnum::Active->value,
            'starts_at' => '2026-03-10',
        ])
        ->assertHasNoActionErrors();

    assertDatabaseHas('company_plans', [
        'company_id' => $this->company->getKey(),
        'monthly_appointments_per_employee' => 1,
    ]);
});
