<?php

declare(strict_types=1);

use App\Models\Users\User;
use TresPontosTech\Company\Models\Company;
use TresPontosTech\Credits\Models\CreditGrant;
use TresPontosTech\PanelAdmin\Filament\Resources\Companies\Pages\EditCompany;
use TresPontosTech\PanelAdmin\Filament\Resources\Companies\RelationManagers\EmployeesRelationManager;
use TresPontosTech\PanelAdmin\Filament\Resources\Users\Pages\EditUser;
use TresPontosTech\PanelAdmin\Filament\Resources\Users\RelationManagers\CreditGrantsRelationManager as UserCreditGrantsRelationManager;

use function Pest\Livewire\livewire;

beforeEach(function (): void {
    actingAsAdmin();
});

it('lists the gifts a user received and ignores company-level grants', function (): void {
    $company = Company::factory()->create();
    $employee = User::factory()->create();
    $company->employees()->attach($employee->getKey());

    // Personal gift to the employee.
    livewire(EmployeesRelationManager::class, ['ownerRecord' => $company, 'pageClass' => EditCompany::class])
        ->callTableAction('grant_extra_credit', $employee, data: ['quantity' => 2, 'justification' => 'Pessoal'])
        ->assertHasNoTableActionErrors();

    // Company-level grant → belongs to the company pool, not the user.
    livewire(EditCompany::class, ['record' => $company->getRouteKey()])
        ->callAction('grant_extra_credit', data: ['quantity' => 1, 'justification' => 'Empresa'])
        ->assertHasNoFormErrors();

    $received = CreditGrant::query()->where('target_user_id', $employee->getKey())->firstOrFail();
    $companyGrant = CreditGrant::query()->where('company_id', $company->getKey())->whereNull('target_user_id')->firstOrFail();

    livewire(UserCreditGrantsRelationManager::class, [
        'ownerRecord' => $employee,
        'pageClass' => EditUser::class,
    ])
        ->assertOk()
        ->assertCanSeeTableRecords([$received])
        ->assertCanNotSeeTableRecords([$companyGrant]);
});

it('does not list gifts made to another user', function (): void {
    $company = Company::factory()->create();
    $employee = User::factory()->create();
    $other = User::factory()->create();
    $company->employees()->attach([$employee->getKey(), $other->getKey()]);

    livewire(EmployeesRelationManager::class, ['ownerRecord' => $company, 'pageClass' => EditCompany::class])
        ->callTableAction('grant_extra_credit', $other, data: ['quantity' => 3, 'justification' => 'Do outro'])
        ->assertHasNoTableActionErrors();

    $otherGift = CreditGrant::query()->where('target_user_id', $other->getKey())->firstOrFail();

    livewire(UserCreditGrantsRelationManager::class, [
        'ownerRecord' => $employee,
        'pageClass' => EditUser::class,
    ])
        ->assertOk()
        ->assertCanNotSeeTableRecords([$otherGift]);
});
