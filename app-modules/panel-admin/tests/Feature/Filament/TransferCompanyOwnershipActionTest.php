<?php

declare(strict_types=1);

use App\Models\Users\User;
use TresPontosTech\Company\Enums\PreviousOwnerOutcome;
use TresPontosTech\Company\Models\Company;
use TresPontosTech\PanelAdmin\Filament\Resources\Companies\Pages\CreateCompany;
use TresPontosTech\PanelAdmin\Filament\Resources\Companies\Pages\EditCompany;
use TresPontosTech\Permissions\Roles;

use function Pest\Livewire\livewire;

beforeEach(function (): void {
    actingAsAdmin();

    $this->previousOwner = User::factory()->create();
    $this->company = Company::factory()->create(['user_id' => $this->previousOwner->getKey()]);
    $this->company->employees()->attach($this->previousOwner->getKey(), ['role' => Roles::CompanyOwner->value]);
});

it('shows the current owner read-only when editing a company', function (): void {
    livewire(EditCompany::class, ['record' => $this->company->getRouteKey()])
        ->assertOk()
        ->assertFormFieldDisabled('user_id')
        ->assertFormSet(['user_id' => $this->previousOwner->getKey()])
        ->assertSee($this->previousOwner->name);
});

it('ignores a submitted owner change on the edit form', function (): void {
    $intruder = User::factory()->create();

    livewire(EditCompany::class, ['record' => $this->company->getRouteKey()])
        ->fillForm(['user_id' => $intruder->getKey(), 'name' => 'Renamed'])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($this->company->fresh())
        ->user_id->toBe($this->previousOwner->getKey())
        ->name->toBe('Renamed');
});

it('still asks for the owner when creating a company', function (): void {
    livewire(CreateCompany::class)
        ->assertOk()
        ->assertFormFieldVisible('user_id');
});

it('transfers ownership through the header action', function (): void {
    $newOwner = User::factory()->create();

    livewire(EditCompany::class, ['record' => $this->company->getRouteKey()])
        ->callAction('transfer_ownership', data: [
            'user_id' => $newOwner->getKey(),
            'previous_owner_outcome' => PreviousOwnerOutcome::KeepAsManager->value,
        ])
        ->assertHasNoActionErrors();

    $company = $this->company->fresh();

    expect($company->user_id)->toBe($newOwner->getKey())
        ->and($newOwner->isCompanyOwner($company))->toBeTrue()
        ->and($this->previousOwner->isCompanyManager($company))->toBeTrue();
});

it('revokes the previous owner access through the header action', function (): void {
    $newOwner = User::factory()->create();

    livewire(EditCompany::class, ['record' => $this->company->getRouteKey()])
        ->callAction('transfer_ownership', data: [
            'user_id' => $newOwner->getKey(),
            'previous_owner_outcome' => PreviousOwnerOutcome::RevokeAccess->value,
        ])
        ->assertHasNoActionErrors();

    $company = $this->company->fresh();

    expect($company->user_id)->toBe($newOwner->getKey())
        ->and($this->previousOwner->tenantRole($company))->toBeNull();
});

it('requires a new owner and a decision about the previous one', function (): void {
    livewire(EditCompany::class, ['record' => $this->company->getRouteKey()])
        ->callAction('transfer_ownership', data: ['user_id' => null, 'previous_owner_outcome' => null])
        ->assertHasActionErrors(['user_id' => 'required', 'previous_owner_outcome' => 'required']);
});

it('is hidden on the default bucket company', function (): void {
    defaultCompanyUser();
    $bucket = Company::default();

    livewire(EditCompany::class, ['record' => $bucket->getRouteKey()])
        ->assertActionHidden('transfer_ownership');
});
