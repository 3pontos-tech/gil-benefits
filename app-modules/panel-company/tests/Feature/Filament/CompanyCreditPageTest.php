<?php

declare(strict_types=1);

use App\Models\Users\User;
use Filament\Actions\Action;
use Filament\Actions\Testing\TestAction;
use TresPontosTech\Billing\Core\Actions\Credit\GrantExtraCredit;
use TresPontosTech\Billing\Core\DTOs\GrantCreditDTO;
use TresPontosTech\Billing\Core\Models\UserCredit;
use TresPontosTech\PanelCompany\Filament\Pages\CompanyCreditPage;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;
use function Pest\Livewire\livewire;

beforeEach(function (): void {
    $this->companyOwner = actingAsCompanyOwner();
    $this->company = filament()->getTenant();

    $this->employees = User::factory(10)->employee()->create();
    $this->company->employees()->attach($this->employees);
    $this->credits = UserCredit::factory()->available()->state([
        'owner_id' => $this->companyOwner->getKey(),
        'holder_id' => $this->companyOwner->getKey(),
        'company_id' => $this->company->getKey(),
    ])
        ->count(11)->create();

});

it('lists credits granted by an admin to the company', function (): void {
    $grant = resolve(GrantExtraCredit::class)->handle(new GrantCreditDTO(
        adminUserId: (string) User::factory()->create()->getKey(),
        company: $this->company,
        quantity: 2,
        justification: 'Cortesia do admin',
    ));

    $adminCredits = UserCredit::query()->where('grant_id', $grant->getKey())->get();

    livewire(CompanyCreditPage::class)
        ->assertOk()
        ->set('tableRecordsPerPage', 50)
        ->assertCanSeeTableRecords($adminCredits);
});

describe('transfer action', function (): void {

    it('should be able to distribute credits manually', function (): void {
        $action = TestAction::make('transfer')->table($this->credits->first());
        $receiver = $this->employees->first();

        livewire(CompanyCreditPage::class)
            ->assertOk()
            ->assertActionExists($action)
            ->assertActionVisible($action)
            ->callAction($action, data: [
                'employee_id' => $receiver->getKey(),
                'quantity' => 1,
            ])
            ->assertHasNoFormErrors();

        assertDatabaseHas(UserCredit::class, [
            'holder_id' => $receiver->getKey(),
        ]);
    });

    test('when transfer credits should distribute the company owner credits', function (): void {
        $action = TestAction::make('transfer')->table($this->credits->first());
        $receiver = $this->employees->first();

        expect($this->companyOwner->credits()->count())->toBe(11);
        livewire(CompanyCreditPage::class)
            ->assertOk()
            ->assertActionExists($action)
            ->assertActionVisible($action)
            ->callAction($action, data: [
                'employee_id' => $receiver->getKey(),
            ])
            ->assertHasNoFormErrors();

        assertDatabaseHas(UserCredit::class, [
            'holder_id' => $receiver->getKey(),
        ]);
        expect($this->companyOwner->credits()->count())->toBe(10);

    });
});

describe('distribute_manually action', function (): void {

    test('should distribute credits manually ', function (): void {
        $action = TestAction::make('distribute_manually')->table($this->credits->first());
        $receiver = $this->employees->first();

        expect($this->companyOwner->credits()->count())->toBe(11);
        livewire(CompanyCreditPage::class)
            ->assertOk()
            ->assertActionExists($action)
            ->assertActionVisible($action)
            ->callAction($action, data: [
                'employee_id' => $receiver->getKey(),
                'quantity' => 3,
            ])
            ->assertHasNoFormErrors();

        expect($receiver->credits()->count())->toBe(3);
        expect($this->companyOwner->credits()->count())->toBe(8);
    });

    test('should not distribute more credits than available ', function (): void {
        $action = TestAction::make('distribute_manually')->table($this->credits->first());
        $receiver = $this->employees->first();

        expect($this->companyOwner->credits()->count())->toBe(11);
        livewire(CompanyCreditPage::class)
            ->assertOk()
            ->assertActionExists($action)
            ->assertActionVisible($action)
            ->callAction($action, data: [
                'employee_id' => $receiver->getKey(),
                'quantity' => 12,
            ])
            ->assertHasFormErrors(['quantity']);

        assertDatabaseMissing(UserCredit::class, [
            'holder_id' => $receiver->getKey(),
        ]);
        expect($this->companyOwner->credits()->count())->toBe(11);
    });

    test('is disabled with tooltip when owner has no available credits', function (): void {
        UserCredit::query()
            ->where('company_id', $this->company->getKey())
            ->update(['status' => 'used']);

        $action = TestAction::make('distribute_manually')->table($this->credits->first());

        livewire(CompanyCreditPage::class)
            ->assertOk()
            ->assertActionDisabled($action)
            ->assertActionExists(
                $action,
                fn (Action $action): bool => $action->getTooltip() === __('panel-company::resources.actions.distribute_manually.disabled_tooltip'),
            );
    });
});

describe('revoke_all_credits action', function (): void {

    beforeEach(function (): void {
        // Distribute some credits to employees so we have something to revoke
        $this->credits->take(5)->each(fn (UserCredit $credit) => $credit->update([
            'holder_id' => $this->employees->first()->getKey(),
            'transferred_at' => now(),
        ]));
    });

    test('revokes all available credits distributed to employees back to owner', function (): void {
        $action = TestAction::make('revoke_all_credits')->table($this->credits->first());

        expect(UserCredit::query()->where('holder_id', $this->employees->first()->getKey())->count())->toBe(5);

        livewire(CompanyCreditPage::class)
            ->assertOk()
            ->assertActionExists($action)
            ->callAction($action)
            ->assertHasNoActionErrors();

        expect(UserCredit::query()->where('holder_id', $this->employees->first()->getKey())->count())->toBe(0);
        expect(UserCredit::query()->where('holder_id', $this->companyOwner->getKey())->count())->toBe(11);
    });

    test('does not revoke credits with status other than available', function (): void {
        $action = TestAction::make('revoke_all_credits')->table($this->credits->first());

        $usedCredit = UserCredit::factory()->used()->state([
            'owner_id' => $this->companyOwner->getKey(),
            'holder_id' => $this->employees->first()->getKey(),
            'company_id' => $this->company->getKey(),
        ])->create();

        livewire(CompanyCreditPage::class)
            ->assertOk()
            ->callAction($action)
            ->assertHasNoActionErrors();

        expect($usedCredit->fresh()->holder_id)->toBe($this->employees->first()->getKey());
    });

    test('does not revoke credits purchased by the employee themselves', function (): void {
        $action = TestAction::make('revoke_all_credits')->table($this->credits->first());
        $employee = $this->employees->first();

        $ownCredit = UserCredit::factory()->available()->state([
            'owner_id' => $employee->getKey(),
            'holder_id' => $employee->getKey(),
            'company_id' => $this->company->getKey(),
        ])->create();

        livewire(CompanyCreditPage::class)
            ->assertOk()
            ->callAction($action)
            ->assertHasNoActionErrors();

        expect($ownCredit->fresh()->holder_id)->toBe($employee->getKey());
    });

    test('is disabled when no credits are distributed to employees', function (): void {
        UserCredit::query()->where('company_id', $this->company->getKey())
            ->update(['holder_id' => $this->companyOwner->getKey()]);

        $action = TestAction::make('revoke_all_credits')->table($this->credits->first());

        livewire(CompanyCreditPage::class)
            ->assertOk()
            ->assertActionDisabled($action);
    });

    test('is enabled when there are credits distributed to employees', function (): void {
        $action = TestAction::make('revoke_all_credits')->table($this->credits->first());

        livewire(CompanyCreditPage::class)
            ->assertOk()
            ->assertActionEnabled($action);
    });

    test('shows tooltip when disabled due to no distributed credits', function (): void {
        UserCredit::query()->where('company_id', $this->company->getKey())
            ->update(['holder_id' => $this->companyOwner->getKey()]);

        $action = TestAction::make('revoke_all_credits')->table($this->credits->first());

        livewire(CompanyCreditPage::class)
            ->assertOk()
            ->assertActionExists(
                $action,
                fn (Action $action): bool => $action->getTooltip() === __('panel-company::resources.actions.revoke_all_credits.disabled_tooltip'),
            );
    });

    test('has no tooltip when enabled', function (): void {
        $action = TestAction::make('revoke_all_credits')->table($this->credits->first());

        livewire(CompanyCreditPage::class)
            ->assertOk()
            ->assertActionExists(
                $action,
                fn (Action $action): bool => $action->getTooltip() === null,
            );
    });
});

describe('table listing', function (): void {

    test('credits purchased by an employee do not appear in the owner listing', function (): void {
        $employee = $this->employees->first();

        UserCredit::factory()->available()->count(3)->state([
            'owner_id' => $employee->getKey(),
            'holder_id' => $employee->getKey(),
            'company_id' => $this->company->getKey(),
        ])->create();

        livewire(CompanyCreditPage::class)
            ->assertOk()
            ->assertCountTableRecords(11);
    });
});

describe('distribute equally', function (): void {

    test('should distribute equally', function (): void {
        $action = TestAction::make('distribute_equally')->table($this->credits->first());

        expect($this->companyOwner->credits()->count())->toBe(11);
        livewire(CompanyCreditPage::class)
            ->assertOk()
            ->assertActionExists($action)
            ->assertActionVisible($action)
            ->callAction($action)
            ->assertHasNoFormErrors();

        $this->employees->fresh()->each(function ($employee): void {
            expect($employee->credits()->count())->toBeOne();
        });

        expect($this->companyOwner->credits()->count())->toBe(1);
    });
    test('should not be able to see the action if is fewer credits than employees', function (): void {
        $action = TestAction::make('distribute_equally')->table($this->credits->first());
        $newEmployees = User::factory(2)->employee()->create();
        $this->company->employees()->attach($newEmployees);

        livewire(CompanyCreditPage::class)
            ->assertOk()
            ->assertActionDisabled($action);
    });

    test('shows tooltip when disabled due to insufficient credits', function (): void {
        $action = TestAction::make('distribute_equally')->table($this->credits->first());
        $newEmployees = User::factory(2)->employee()->create();
        $this->company->employees()->attach($newEmployees);

        livewire(CompanyCreditPage::class)
            ->assertOk()
            ->assertActionExists(
                $action,
                fn (Action $action): bool => $action->getTooltip() === __('panel-company::resources.actions.distribute_equally.disabled_tooltip'),
            );
    });

    test('has no tooltip when enabled', function (): void {
        $action = TestAction::make('distribute_equally')->table($this->credits->first());

        livewire(CompanyCreditPage::class)
            ->assertOk()
            ->assertActionExists(
                $action,
                fn (Action $action): bool => $action->getTooltip() === null,
            );
    });
});
