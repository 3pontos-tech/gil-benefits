<?php

use App\Filament\FilamentPanel;
use App\Models\Users\User;
use Filament\Actions\Testing\TestAction;
use TresPontosTech\Company\Models\Company;
use TresPontosTech\Tenant\Filament\Actions\TenantSecretKeyRotationPanelAction;
use TresPontosTech\Tenant\Filament\Pages\Tenancy\EditTenantProfile;
use TresPontosTech\Tenant\Filament\Pages\Tenancy\RegisterTenant;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseCount;
use function Pest\Laravel\assertDatabaseHas;
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

it('should render', function (): void {
    livewire(EditTenantProfile::class)
        ->assertOk();
});

it('should be able to generate a new token', function (): void {
    $action = TestAction::make(TenantSecretKeyRotationPanelAction::class)->schemaComponent( 'tenant-secret-key-rotation');
    $oldToken = $this->company->integration_access_key;

    livewire(EditTenantProfile::class)
        ->assertOk()
        ->ddBody()
        ->mountAction($action)
        ->callAction($action)
        ->assertHasNoFormErrors();

    $this->company->refresh();
    expect($oldToken)->not()->toBe($this->company->refresh()->integration_access_key);
});
