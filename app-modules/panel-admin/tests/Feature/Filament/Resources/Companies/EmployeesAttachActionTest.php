<?php

declare(strict_types=1);

use App\Models\Users\User;
use Filament\Actions\Testing\TestAction;
use TresPontosTech\Company\Models\Company;
use TresPontosTech\PanelAdmin\Filament\Resources\Companies\Pages\EditCompany;
use TresPontosTech\PanelAdmin\Filament\Resources\Companies\RelationManagers\EmployeesRelationManager;
use TresPontosTech\Permissions\Roles;

use function Pest\Livewire\livewire;

beforeEach(function (): void {
    actingAsSuperAdmin();
    $this->company = Company::factory()->create();
});

it('attaches an existing user as an employee', function (): void {
    $person = User::factory()->create();

    livewire(EmployeesRelationManager::class, ['ownerRecord' => $this->company, 'pageClass' => EditCompany::class])
        ->callAction(TestAction::make('attach')->table(), data: ['recordId' => $person->getKey()])
        ->assertHasNoActionErrors();

    expect($this->company->employees()->whereKey($person->getKey())->first()?->pivot->role)->toBe(Roles::Employee);
});

it('refuses to attach someone already employed elsewhere, with a message instead of an error', function (): void {
    $elsewhere = Company::factory()->create();
    $person = User::factory()->create();
    $elsewhere->employees()->attach($person->getKey());

    livewire(EmployeesRelationManager::class, ['ownerRecord' => $this->company, 'pageClass' => EditCompany::class])
        ->callAction(TestAction::make('attach')->table(), data: ['recordId' => $person->getKey()])
        ->assertNotified();

    expect($this->company->employees()->whereKey($person->getKey())->exists())->toBeFalse();
});
