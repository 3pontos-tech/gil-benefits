<?php

declare(strict_types=1);

use App\Filament\FilamentPanel;
use App\Models\Users\User;
use TresPontosTech\Billing\Core\Enums\CompanyPlanStatusEnum;
use TresPontosTech\Billing\Core\Models\CompanyPlan;
use TresPontosTech\Company\Models\Company;
use TresPontosTech\Permissions\PermissionsEnum;
use TresPontosTech\Permissions\Roles;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

/*
 * Scenario 1 — Real isolation.
 * The owner has no relationship with the other company: no access at all.
 */
describe('tenant isolation: no relationship with the other company', function (): void {

    beforeEach(function (): void {
        $this->owner = User::factory()->companyOwner()->create();
        $this->ownCompany = Company::factory()->recycle($this->owner)->create();
        $this->ownCompany->employees()->attach($this->owner->getKey());

        CompanyPlan::factory()->create([
            'company_id' => $this->ownCompany->getKey(),
            'status' => CompanyPlanStatusEnum::Active->value,
            'monthly_appointments_per_employee' => 1,
            'starts_at' => now()->subDay(),
            'seats' => 10,
        ]);

        // Third-party company — the owner is neither its owner nor a member.
        $otherOwner = User::factory()->companyOwner()->create();
        $this->otherCompany = Company::factory()->recycle($otherOwner)->create();
        $this->otherCompany->employees()->attach($otherOwner->getKey());

        filament()->setCurrentPanel(FilamentPanel::Company->value);
        actingAs($this->owner);
    });

    it('returns 404 when trying to access another company dashboard', function (): void {
        get(route('filament.company.pages.dashboard', ['tenant' => $this->otherCompany->slug]))
            ->assertNotFound();
    });

    it('returns 404 when trying to access another company credits page', function (): void {
        get(route('filament.company.pages.credits', ['tenant' => $this->otherCompany->slug]))
            ->assertNotFound();
    });

    it('returns 404 when trying to access another company profile', function (): void {
        get(route('filament.company.tenant.profile', ['tenant' => $this->otherCompany->slug]))
            ->assertNotFound();
    });

    it('returns 404 when trying to access another company billing', function (): void {
        get(route('filament.company.tenant.billing', ['tenant' => $this->otherCompany->slug]))
            ->assertNotFound();
    });

    it('allows access to own company dashboard', function (): void {
        get(route('filament.company.pages.dashboard', ['tenant' => $this->ownCompany->slug]))
            ->assertOk();
    });

    it('canAccessTenant returns false for a third-party company', function (): void {
        expect($this->owner->canAccessTenant($this->otherCompany))->toBeFalse();
    });

    it('canAccessTenant returns true for own company', function (): void {
        expect($this->owner->canAccessTenant($this->ownCompany))->toBeTrue();
    });

    it('getTenants does not expose third-party company', function (): void {
        $tenants = $this->owner->getTenants(filament()->getCurrentPanel());

        expect($tenants->contains($this->otherCompany))->toBeFalse()
            ->and($tenants->contains($this->ownCompany))->toBeTrue();
    });

    it('getTenants returns only the owned company', function (): void {
        $tenants = $this->owner->getTenants(filament()->getCurrentPanel());

        expect($tenants)->toHaveCount(1)
            ->and($tenants->first()->getKey())->toBe($this->ownCompany->getKey());
    });
});

/*
 * Scenario 2 — Scoped role (the actual proof of the fix).
 * The owner of company A is added as an EMPLOYEE of company B.
 * They can access B (a legitimate member), but do NOT inherit the owner role:
 * in B they are an employee, in A they remain the owner. Privilege does not leak.
 */
describe('tenant scoped role: owner of A is an employee of B', function (): void {

    beforeEach(function (): void {
        $this->owner = User::factory()->companyOwner()->create();
        $this->ownCompany = Company::factory()->recycle($this->owner)->create();
        $this->ownCompany->employees()->attach($this->owner->getKey());

        // Another owner's company, where our owner joins as an EMPLOYEE.
        $otherOwner = User::factory()->companyOwner()->create();
        $this->otherCompany = Company::factory()->recycle($otherOwner)->create();
        $this->otherCompany->employees()->attach($otherOwner->getKey());
        $this->otherCompany->employees()->attach(
            $this->owner->getKey(),
            ['role' => Roles::Employee->value],
        );

        filament()->setCurrentPanel(FilamentPanel::Company->value);
        actingAs($this->owner);
    });

    it('cannot access company B in the management panel (only an employee there)', function (): void {
        // beforeEach set the company panel as current.
        expect($this->owner->canAccessTenant($this->otherCompany))->toBeFalse();
    });

    it('the management panel lists only the company it owns', function (): void {
        $tenants = $this->owner->getTenants(filament()->getCurrentPanel());

        expect($tenants->contains($this->otherCompany))->toBeFalse()
            ->and($tenants->contains($this->ownCompany))->toBeTrue();
    });

    it('does not inherit the owner role in the other company — it is an employee there', function (): void {
        expect($this->owner->isCompanyOwner($this->otherCompany))->toBeFalse()
            ->and($this->owner->isCompanyManager($this->otherCompany))->toBeFalse()
            ->and($this->owner->isEmployee($this->otherCompany))->toBeTrue();
    });

    it('keeps the owner role in its own company', function (): void {
        expect($this->owner->isCompanyOwner($this->ownCompany))->toBeTrue();
    });

    it('owner permission (manage users) applies in its own company but NOT in the other', function (): void {
        $viewAnyUser = PermissionsEnum::ViewAny->buildPermissionFor(User::class);

        expect($this->owner->hasTenantPermission($viewAnyUser, $this->ownCompany))->toBeTrue()
            ->and($this->owner->hasTenantPermission($viewAnyUser, $this->otherCompany))->toBeFalse();
    });

    it('can access company B in the app panel as a regular member', function (): void {
        filament()->setCurrentPanel(FilamentPanel::User->value);

        expect($this->owner->canAccessTenant($this->otherCompany))->toBeTrue();

        $tenants = $this->owner->getTenants(filament()->getCurrentPanel());
        expect($tenants->contains($this->otherCompany))->toBeTrue();
    });
});
