<?php

declare(strict_types=1);

use App\Models\Users\User;
use TresPontosTech\Billing\Core\Models\CreditGrant;
use TresPontosTech\Company\Models\Company;
use TresPontosTech\PanelAdmin\Filament\Resources\Companies\Pages\EditCompany;
use TresPontosTech\PanelAdmin\Filament\Resources\Companies\RelationManagers\CreditGrantsRelationManager;
use TresPontosTech\PanelAdmin\Filament\Resources\Companies\RelationManagers\EmployeesRelationManager;

use function Pest\Livewire\livewire;

beforeEach(function (): void {
    actingAsAdmin();
});

it('shows a grant in the company credits history after it is granted from the panel', function (): void {
    $company = Company::factory()->create();

    livewire(EditCompany::class, ['record' => $company->getRouteKey()])
        ->callAction('grant_extra_credit', data: [
            'quantity' => 2,
            'justification' => 'Cortesia por atraso no onboarding',
        ])
        ->assertHasNoActionErrors();

    $grant = CreditGrant::query()->where('company_id', $company->getKey())->latest()->firstOrFail();

    livewire(CreditGrantsRelationManager::class, [
        'ownerRecord' => $company,
        'pageClass' => EditCompany::class,
    ])
        ->assertOk()
        ->assertCanSeeTableRecords([$grant]);
});

it('does not show a grant that belongs to another company', function (): void {
    $company = Company::factory()->create();
    $other = Company::factory()->create();

    livewire(EditCompany::class, ['record' => $other->getRouteKey()])
        ->callAction('grant_extra_credit', data: ['quantity' => 1, 'justification' => 'Outra empresa'])
        ->assertHasNoActionErrors();

    $otherGrant = CreditGrant::query()->where('company_id', $other->getKey())->latest()->firstOrFail();

    livewire(CreditGrantsRelationManager::class, [
        'ownerRecord' => $company,
        'pageClass' => EditCompany::class,
    ])
        ->assertOk()
        ->assertCanNotSeeTableRecords([$otherGrant]);
});

it('hides personal gifts to a user from the company credits history', function (): void {
    $company = Company::factory()->create();
    $employee = User::factory()->create();
    $company->employees()->attach($employee->getKey());

    // Company-level grant → belongs in the company history.
    livewire(EditCompany::class, ['record' => $company->getRouteKey()])
        ->callAction('grant_extra_credit', data: ['quantity' => 2, 'justification' => 'Para a empresa'])
        ->assertHasNoFormErrors();

    // Personal gift to the employee (from the employees list) → belongs to the user.
    livewire(EmployeesRelationManager::class, ['ownerRecord' => $company, 'pageClass' => EditCompany::class])
        ->callTableAction('grant_extra_credit', $employee, data: ['quantity' => 1, 'justification' => 'Pessoal'])
        ->assertHasNoTableActionErrors();

    $companyGrant = CreditGrant::query()->where('company_id', $company->getKey())->whereNull('target_user_id')->firstOrFail();
    $personalGrant = CreditGrant::query()->where('company_id', $company->getKey())->where('target_user_id', $employee->getKey())->firstOrFail();

    livewire(CreditGrantsRelationManager::class, [
        'ownerRecord' => $company,
        'pageClass' => EditCompany::class,
    ])
        ->assertOk()
        ->assertCanSeeTableRecords([$companyGrant])
        ->assertCanNotSeeTableRecords([$personalGrant]);
});

it('can switch the filter to show gifts made to the company users', function (): void {
    $company = Company::factory()->create();
    $employee = User::factory()->create();
    $company->employees()->attach($employee->getKey());

    livewire(EditCompany::class, ['record' => $company->getRouteKey()])
        ->callAction('grant_extra_credit', data: ['quantity' => 2, 'justification' => 'Para a empresa'])
        ->assertHasNoFormErrors();

    livewire(EmployeesRelationManager::class, ['ownerRecord' => $company, 'pageClass' => EditCompany::class])
        ->callTableAction('grant_extra_credit', $employee, data: ['quantity' => 1, 'justification' => 'Pessoal'])
        ->assertHasNoTableActionErrors();

    $companyGrant = CreditGrant::query()->where('company_id', $company->getKey())->whereNull('target_user_id')->firstOrFail();
    $personalGrant = CreditGrant::query()->where('company_id', $company->getKey())->whereNotNull('target_user_id')->firstOrFail();

    livewire(CreditGrantsRelationManager::class, [
        'ownerRecord' => $company,
        'pageClass' => EditCompany::class,
    ])
        ->filterTable('recipient_scope', 'users')
        ->assertCanSeeTableRecords([$personalGrant])
        ->assertCanNotSeeTableRecords([$companyGrant]);
});
