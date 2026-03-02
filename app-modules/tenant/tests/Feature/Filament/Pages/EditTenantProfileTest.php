<?php

use App\Filament\FilamentPanel;
use App\Models\Users\User;
use Filament\Actions\Testing\TestAction;
use TresPontosTech\Company\Models\Company;
use TresPontosTech\Tenant\Actions\TenantSecretKeyRotationAction;
use TresPontosTech\Tenant\Filament\Actions\TenantSecretKeyRotationPanelAction;
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
    actingAsAdmin(FilamentPanel::Company);
    filament()->setTenant($this->company);
});

it('should render edit tenant profile page', function (): void {
    livewire(EditTenantProfile::class)
        ->assertOk();
});

describe('change tenant secret action tests', function (): void {
    it('should be able to generate a new token through filament action', function (): void {
        $action = TestAction::make(TenantSecretKeyRotationPanelAction::class);
        $oldKey = $this->company->integration_access_key;
        livewire(EditTenantProfile::class)
            ->assertOk()
            ->mountAction($action)
            ->callAction($action)
            ->assertHasNoFormErrors();

        expect($this->company->refresh()->integration_access_key)->not->toBe($oldKey);
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

});

describe('authorization', function (): void {

    test('only company owner or admin can see the page', function (): void {
        $invalidUser = User::factory()->employee()->create();
        $this->company->employees()->attach($invalidUser);
        actingAs($invalidUser);

        livewire(EditTenantProfile::class)
            ->assertNotFound();
    });

    test('another company owner can not see the page', function (): void {

        $anotherCompanyOwner = User::factory()->companyOwner()->create();
        Company::factory()->for($anotherCompanyOwner, 'owner')->create();

        actingAs($anotherCompanyOwner);

        livewire(EditTenantProfile::class)
            ->assertForbidden();
    });

    test('company owner can see the page', function (): void {

        $companyOwner = User::factory()->companyOwner()->create();
        $anotherCompany = Company::factory()->for($companyOwner, 'owner')->create();
        $anotherCompany->subscriptions()->create([
            'type' => 'company',
            'stripe_id' => '1234568',
            'stripe_status' => 'active',
        ]);

        filament()->setTenant($anotherCompany);

        actingAs($companyOwner);

        livewire(EditTenantProfile::class)
            ->assertOk();
    });
});
