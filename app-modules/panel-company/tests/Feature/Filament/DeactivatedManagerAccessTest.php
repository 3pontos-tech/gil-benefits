<?php

declare(strict_types=1);

use App\Filament\FilamentPanel;
use App\Models\Users\User;
use TresPontosTech\Billing\Core\Enums\CompanyPlanStatusEnum;
use TresPontosTech\Billing\Core\Models\CompanyPlan;
use TresPontosTech\Company\Models\Company;
use TresPontosTech\PanelCompany\Filament\Pages\Tenancy\EditTenantProfile;
use TresPontosTech\PanelCompany\Support\PartnerVoucherAccess;
use TresPontosTech\PanelCompany\Support\PartnerVoucherUrls;
use TresPontosTech\Permissions\PermissionsEnum;
use TresPontosTech\Permissions\Roles;
use TresPontosTech\Vouchers\Models\VoucherBatch;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;
use function Pest\Livewire\livewire;

beforeEach(function (): void {
    $this->owner = User::factory()->companyOwner()->create();
    $this->company = Company::factory()->recycle($this->owner)->create();
    $this->company->employees()->attach($this->owner->getKey());

    CompanyPlan::factory()->create([
        'company_id' => $this->company->getKey(),
        'status' => CompanyPlanStatusEnum::Active->value,
        'monthly_appointments_per_employee' => 1,
        'starts_at' => now()->subDay(),
        'seats' => 10,
    ]);

    $this->manager = User::factory()->companyManager()->create();
    $this->company->employees()->attach($this->manager->getKey(), [
        'role' => Roles::CompanyManager->value,
        'active' => false,
    ]);

    filament()->setCurrentPanel(FilamentPanel::Company->value);
});

it('has no role in the company once deactivated', function (): void {
    expect($this->manager->tenantRole($this->company))->toBeNull()
        ->and($this->manager->isCompanyManager($this->company))->toBeFalse()
        ->and($this->manager->isCompanyOwner($this->company))->toBeFalse()
        ->and($this->manager->isEmployee($this->company))->toBeFalse();
});

it('no longer manages any company', function (): void {
    expect($this->manager->managesAnyCompany())->toBeFalse()
        ->and($this->manager->canAccessPanel(filament()->getCurrentPanel()))->toBeFalse();
});

it('cannot access the company tenant in the management panel', function (): void {
    expect($this->manager->canAccessTenant($this->company))->toBeFalse();
});

it('loses the manager permissions in the company', function (): void {
    $viewAnyUser = PermissionsEnum::ViewAny->buildPermissionFor(User::class);

    expect($this->manager->hasTenantPermission($viewAnyUser, $this->company))->toBeFalse();
});

it('cannot see the partner voucher program', function (): void {
    expect(PartnerVoucherAccess::allows($this->manager, $this->company))->toBeFalse();
});

it('is kept out of the company panel pages by URL', function (string $route): void {
    actingAs($this->manager);

    get(route($route, ['tenant' => $this->company->slug]))
        ->assertStatus(302)
        ->assertRedirect();
})->with([
    'dashboard' => ['filament.company.pages.dashboard'],
    'credits page' => ['filament.company.pages.credits'],
    'profile' => ['filament.company.tenant.profile'],
]);

it('cannot open the members page through a Livewire request', function (): void {
    actingAs($this->manager);
    filament()->setTenant($this->company);

    livewire(EditTenantProfile::class)->assertNotFound();
});

it('cannot download the partner voucher sheet', function (): void {
    $plan = CompanyPlan::factory()->active()->creditsOnly()->for($this->company)->create();
    $batch = VoucherBatch::factory()->forPlan($plan)->create();

    actingAs($this->manager);

    get(PartnerVoucherUrls::pdf($batch))->assertForbidden();

    $this->company->employees()->updateExistingPivot($this->manager->getKey(), ['active' => true]);

    get(PartnerVoucherUrls::pdf($batch))->assertNotFound();
});

it('gets everything back once reactivated', function (): void {
    $this->company->employees()->updateExistingPivot($this->manager->getKey(), ['active' => true]);

    expect($this->manager->isCompanyManager($this->company))->toBeTrue()
        ->and($this->manager->managesAnyCompany())->toBeTrue()
        ->and($this->manager->canAccessTenant($this->company))->toBeTrue();
});

it('keeps the owner in charge even with an inactive membership row', function (): void {
    $this->company->employees()->updateExistingPivot($this->owner->getKey(), ['active' => false]);

    expect($this->owner->isCompanyOwner($this->company))->toBeTrue()
        ->and($this->owner->canAccessTenant($this->company))->toBeTrue();
});
