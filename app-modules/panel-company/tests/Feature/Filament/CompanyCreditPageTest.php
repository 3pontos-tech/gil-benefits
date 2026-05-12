<?php

declare(strict_types=1);

use App\Models\Users\User;
use Filament\Actions\Testing\TestAction;
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
});
