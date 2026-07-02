<?php

declare(strict_types=1);

use App\Models\Users\User;
use TresPontosTech\Billing\Core\Models\CreditGrant;
use TresPontosTech\Billing\Core\Models\UserCredit;
use TresPontosTech\Company\Models\Company;
use TresPontosTech\PanelAdmin\Filament\Resources\Companies\Pages\EditCompany;
use TresPontosTech\PanelAdmin\Filament\Resources\Companies\RelationManagers\EmployeesRelationManager;
use TresPontosTech\PanelAdmin\Filament\Resources\Users\Pages\EditUser;

use function Pest\Livewire\livewire;

beforeEach(function (): void {
    actingAsAdmin();
});

it('grants extra credit to a company from its edit page', function (): void {
    $company = Company::factory()->create();

    livewire(EditCompany::class, ['record' => $company->getRouteKey()])
        ->callAction('grant_extra_credit', data: [
            'quantity' => 3,
            'justification' => 'Cortesia por atraso no onboarding',
        ])
        ->assertHasNoActionErrors();

    expect(CreditGrant::query()->where('company_id', $company->getKey())->count())->toBe(1)
        ->and(UserCredit::query()->where('company_id', $company->getKey())->count())->toBe(3);
});

it('validates quantity and justification on the grant action', function (): void {
    $company = Company::factory()->create();

    livewire(EditCompany::class, ['record' => $company->getRouteKey()])
        ->callAction('grant_extra_credit', data: [
            'quantity' => 0,
            'justification' => '',
        ])
        ->assertHasActionErrors([
            'quantity' => ['min'],
            'justification' => ['required'],
        ]);

    expect(CreditGrant::query()->count())->toBe(0);
});

it('grants extra credit to a user from their edit page', function (): void {
    $company = Company::factory()->create();
    $user = User::factory()->create();
    $company->employees()->attach($user->getKey());

    livewire(EditUser::class, ['record' => $user->getKey()])
        ->callAction('grant_extra_credit', data: [
            'quantity' => 2,
            'justification' => 'Compensação',
        ])
        ->assertHasNoActionErrors();

    expect(CreditGrant::query()->where('target_user_id', $user->getKey())->count())->toBe(1)
        ->and(UserCredit::query()->where('holder_id', $user->getKey())->count())->toBe(2);
});

it('grants extra credit to an employee from the company employees list', function (): void {
    $company = Company::factory()->create();
    $employee = User::factory()->create();
    $company->employees()->attach($employee->getKey());

    livewire(EmployeesRelationManager::class, [
        'ownerRecord' => $company,
        'pageClass' => EditCompany::class,
    ])
        ->callTableAction('grant_extra_credit', $employee, data: [
            'quantity' => 4,
            'justification' => 'Cortesia direta da lista',
        ])
        ->assertHasNoTableActionErrors();

    expect(CreditGrant::query()
        ->where('company_id', $company->getKey())
        ->where('target_user_id', $employee->getKey())
        ->count())->toBe(1)
        ->and(UserCredit::query()->where('holder_id', $employee->getKey())->count())->toBe(4);
});
