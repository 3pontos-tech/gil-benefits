<?php

declare(strict_types=1);

use App\Filament\FilamentPanel;
use App\Models\Users\User;
use TresPontosTech\Billing\Core\Enums\CompanyPlanStatusEnum;
use TresPontosTech\Billing\Core\Models\CompanyPlan;
use TresPontosTech\Company\Models\Company;
use TresPontosTech\Permissions\Roles;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

describe('company manager tenant isolation', function (): void {

    beforeEach(function (): void {
        $realOwner = User::factory()->companyOwner()->create();

        $this->managedCompany = Company::factory()->recycle($realOwner)->create();
        $this->managedCompany->employees()->attach($realOwner->getKey());

        CompanyPlan::factory()->create([
            'company_id' => $this->managedCompany->getKey(),
            'status' => CompanyPlanStatusEnum::Active->value,
            'monthly_appointments_per_employee' => 1,
            'starts_at' => now()->subDay(),
            'seats' => 10,
        ]);

        $this->manager = User::factory()->companyManager()->create();
        $this->managedCompany->employees()->attach(
            $this->manager->getKey(),
            ['role' => Roles::CompanyManager->value],
        );

        $otherOwner = User::factory()->companyOwner()->create();
        $this->otherCompany = Company::factory()->recycle($otherOwner)->create();
        $this->otherCompany->employees()->attach($otherOwner->getKey());

        filament()->setCurrentPanel(FilamentPanel::Company->value);
        actingAs($this->manager);
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

    it('allows access to the managed company dashboard', function (): void {
        get(route('filament.company.pages.dashboard', ['tenant' => $this->managedCompany->slug]))
            ->assertOk();
    });

    it('canAccessTenant returns false for a company without pivot association', function (): void {
        expect($this->manager->canAccessTenant($this->otherCompany))->toBeFalse();
    });

    it('canAccessTenant returns true for a company linked in the pivot', function (): void {
        expect($this->manager->canAccessTenant($this->managedCompany))->toBeTrue();
    });

    it('getTenants does not expose unlinked company', function (): void {
        $tenants = $this->manager->getTenants(filament()->getCurrentPanel());

        expect($tenants->contains($this->otherCompany))->toBeFalse()
            ->and($tenants->contains($this->managedCompany))->toBeTrue();
    });

    it('getTenants returns only the managed company', function (): void {
        $tenants = $this->manager->getTenants(filament()->getCurrentPanel());

        expect($tenants)->toHaveCount(1)
            ->and($tenants->first()->getKey())->toBe($this->managedCompany->getKey());
    });
});
