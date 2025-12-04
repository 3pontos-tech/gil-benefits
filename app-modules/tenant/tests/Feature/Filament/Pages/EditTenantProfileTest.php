<?php

use App\Filament\FilamentPanel;
use App\Models\Users\User;
use TresPontosTech\Company\Models\Company;
use TresPontosTech\Tenant\Actions\TenantSecretKeyRotationAction;
use TresPontosTech\Tenant\Filament\Pages\Tenancy\EditTenantProfile;

use function Pest\Laravel\actingAs;
use function Pest\Livewire\livewire;

beforeEach(function (): void {
    $this->company = Company::factory()->create();
    $this->company->subscriptions()->create([
        'type' => 'company',
        'stripe_id' => '12345',
        'stripe_status' => 'active',
    ]);
    actingAs(User::factory()->create());
    filament()->setCurrentPanel(FilamentPanel::Company->value);
    filament()->setTenant($this->company);
});

it('should render edit tenant profile page', function (): void {
    livewire(EditTenantProfile::class)
        ->assertOk();
});

it('should render generate key button', function (): void {
    livewire(EditTenantProfile::class)
        ->assertSee('Gerar nova chave');
});

it('should be able to generate a new token', function (): void {
    $oldToken = $this->company->integration_access_key;
    resolve(TenantSecretKeyRotationAction::class)->generate($this->company);

    $this->company->refresh();
    expect($this->company->integration_access_key)
        ->not()->toBe($oldToken)
        ->and($this->company->integration_access_key)
        ->not()->toBeNull();
});
