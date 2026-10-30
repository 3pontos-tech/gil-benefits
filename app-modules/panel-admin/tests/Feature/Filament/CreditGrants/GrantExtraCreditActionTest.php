<?php

declare(strict_types=1);

use App\Models\Users\User;
use TresPontosTech\Company\Models\Company;
use TresPontosTech\Credits\Models\CreditGrant;
use TresPontosTech\Credits\Models\UserCredit;
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

it('lands the gift in the employer by default, not in the default bucket', function (): void {
    $user = defaultCompanyUser();
    $employer = Company::factory()->create();
    $employer->employees()->attach($user->getKey());

    livewire(EditUser::class, ['record' => $user->getKey()])
        ->callAction('grant_extra_credit', data: [
            'quantity' => 1,
            'justification' => 'Compensação',
        ])
        ->assertHasNoActionErrors();

    expect(CreditGrant::query()->where('target_user_id', $user->getKey())->value('company_id'))->toBe($employer->getKey())
        ->and(UserCredit::query()->where('holder_id', $user->getKey())->value('company_id'))->toBe($employer->getKey());
});

it('lands the gift in the chosen company when the user has more than one', function (): void {
    $user = defaultCompanyUser();
    $first = Company::factory()->create();
    $second = Company::factory()->create();
    $first->employees()->attach($user->getKey());
    $second->employees()->attach($user->getKey());

    livewire(EditUser::class, ['record' => $user->getKey()])
        ->callAction('grant_extra_credit', data: [
            'company_id' => $second->getKey(),
            'quantity' => 1,
            'justification' => 'Compensação',
        ])
        ->assertHasNoActionErrors();

    expect(CreditGrant::query()->where('target_user_id', $user->getKey())->value('company_id'))->toBe($second->getKey());
});

it('offers the default bucket only when it is the sole membership', function (): void {
    $user = defaultCompanyUser();

    livewire(EditUser::class, ['record' => $user->getKey()])
        ->callAction('grant_extra_credit', data: [
            'quantity' => 1,
            'justification' => 'Compensação',
        ])
        ->assertHasNoActionErrors();

    expect(CreditGrant::query()->where('target_user_id', $user->getKey())->value('company_id'))->toBe(Company::default()->getKey());
});

it('rejects a company the user does not belong to', function (): void {
    $user = defaultCompanyUser();
    $employer = Company::factory()->create();
    $employer->employees()->attach($user->getKey());
    $stranger = Company::factory()->create();

    livewire(EditUser::class, ['record' => $user->getKey()])
        ->callAction('grant_extra_credit', data: [
            'company_id' => $stranger->getKey(),
            'quantity' => 1,
            'justification' => 'Compensação',
        ])
        ->assertHasActionErrors(['company_id']);

    expect(CreditGrant::query()->count())->toBe(0);
});

it('ignores a deactivated membership when choosing the company', function (): void {
    $user = defaultCompanyUser();
    $former = Company::factory()->create();
    $current = Company::factory()->create();
    $former->employees()->attach($user->getKey(), ['active' => false]);
    $current->employees()->attach($user->getKey());

    livewire(EditUser::class, ['record' => $user->getKey()])
        ->callAction('grant_extra_credit', data: [
            'company_id' => $former->getKey(),
            'quantity' => 1,
            'justification' => 'Compensação',
        ])
        ->assertHasActionErrors(['company_id']);

    expect(CreditGrant::query()->count())->toBe(0);
});
