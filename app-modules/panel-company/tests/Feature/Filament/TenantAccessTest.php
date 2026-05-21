<?php

declare(strict_types=1);

use App\Filament\FilamentPanel;
use App\Models\Users\User;
use TresPontosTech\Billing\Core\Enums\CompanyPlanStatusEnum;
use TresPontosTech\Billing\Core\Models\CompanyPlan;
use TresPontosTech\Company\Models\Company;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

describe('company owner tenant isolation', function (): void {

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

        $otherOwner = User::factory()->companyOwner()->create();
        $this->otherCompany = Company::factory()->recycle($otherOwner)->create();
        $this->otherCompany->employees()->attach($otherOwner->getKey());

        $this->otherCompany->employees()->attach($this->owner->getKey());

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
