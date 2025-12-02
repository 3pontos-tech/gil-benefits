<?php

use App\Filament\FilamentPanel;
use App\Models\Users\User;
use Filament\Facades\Filament;
use TresPontosTech\Company\Enums\CompanyRoleEnum;
use TresPontosTech\Company\Models\Company;

use function Pest\Laravel\actingAs;

describe('Partner Collaborator Access Control Integration', function () {

    test('partner collaborator can access user panel dashboard', function () {
        $company = Company::factory()->create(['partner_code' => 'PARTNER123']);
        $user = User::factory()->create();
        $user->companies()->attach($company, ['role' => CompanyRoleEnum::Employee]);

        actingAs($user);
        Filament::setCurrentPanel(FilamentPanel::User->value);

        // Test that the user can access the user panel
        expect($user->canAccessPanel(Filament::getCurrentPanel()))->toBeTrue();
    });

    test('partner collaborator cannot access admin panel', function () {
        $company = Company::factory()->create(['partner_code' => 'PARTNER123']);
        $user = User::factory()->create();
        $user->companies()->attach($company, ['role' => CompanyRoleEnum::Employee]);

        actingAs($user);

        // Mock admin panel
        $adminPanel = Mockery::mock(\Filament\Panel::class);
        $adminPanel->shouldReceive('getId')->andReturn(FilamentPanel::Admin->value);

        expect($user->canAccessPanel($adminPanel))->toBeFalse();
    });

    test('partner collaborator cannot access company panel', function () {
        $company = Company::factory()->create(['partner_code' => 'PARTNER123']);
        $user = User::factory()->create();
        $user->companies()->attach($company, ['role' => CompanyRoleEnum::Employee]);

        actingAs($user);

        // Mock company panel
        $companyPanel = Mockery::mock(\Filament\Panel::class);
        $companyPanel->shouldReceive('getId')->andReturn(FilamentPanel::Company->value);

        expect($user->canAccessPanel($companyPanel))->toBeFalse();
    });

    test('partner collaborator cannot access consultant panel', function () {
        $company = Company::factory()->create(['partner_code' => 'PARTNER123']);
        $user = User::factory()->create();
        $user->companies()->attach($company, ['role' => CompanyRoleEnum::Employee]);

        actingAs($user);

        // Mock consultant panel
        $consultantPanel = Mockery::mock(\Filament\Panel::class);
        $consultantPanel->shouldReceive('getId')->andReturn(FilamentPanel::Consultant->value);

        expect($user->canAccessPanel($consultantPanel))->toBeFalse();
    });

    test('partner collaborator cannot access guest panel', function () {
        $company = Company::factory()->create(['partner_code' => 'PARTNER123']);
        $user = User::factory()->create();
        $user->companies()->attach($company, ['role' => CompanyRoleEnum::Employee]);

        actingAs($user);

        // Mock guest panel
        $guestPanel = Mockery::mock(\Filament\Panel::class);
        $guestPanel->shouldReceive('getId')->andReturn(FilamentPanel::Guest->value);

        expect($user->canAccessPanel($guestPanel))->toBeFalse();
    });

    test('partner collaborator has tenant isolation', function () {
        $partnerCompany = Company::factory()->create(['partner_code' => 'PARTNER123']);
        $otherCompany = Company::factory()->create(['partner_code' => 'OTHER456']);
        $user = User::factory()->create();
        $user->companies()->attach($partnerCompany, ['role' => CompanyRoleEnum::Employee]);

        actingAs($user);

        // User should only have access to their partner company
        expect($user->canAccessTenant($partnerCompany))->toBeTrue();
        expect($user->canAccessTenant($otherCompany))->toBeFalse();

        // User should only see their partner company in tenants list
        $panel = Mockery::mock(\Filament\Panel::class);
        $tenants = $user->getTenants($panel);

        expect($tenants)->toHaveCount(1);
        expect($tenants->first()->id)->toBe($partnerCompany->id);
    });

    test('non-partner collaborator has full access', function () {
        $company1 = Company::factory()->create(['partner_code' => null]);
        $company2 = Company::factory()->create(['partner_code' => null]);
        $user = User::factory()->create();
        $user->companies()->attach($company1, ['role' => CompanyRoleEnum::Employee]);
        $user->companies()->attach($company2, ['role' => CompanyRoleEnum::Employee]);

        actingAs($user);

        // Mock panels
        $userPanel = Mockery::mock(\Filament\Panel::class);
        $userPanel->shouldReceive('getId')->andReturn(FilamentPanel::User->value);

        $adminPanel = Mockery::mock(\Filament\Panel::class);
        $adminPanel->shouldReceive('getId')->andReturn(FilamentPanel::Admin->value);

        // User should have access to all panels
        expect($user->canAccessPanel($userPanel))->toBeTrue();
        expect($user->canAccessPanel($adminPanel))->toBeTrue();

        // User should have access to all their companies
        expect($user->canAccessTenant($company1))->toBeTrue();
        expect($user->canAccessTenant($company2))->toBeTrue();

        // User should see all their companies in tenants list
        $panel = Mockery::mock(\Filament\Panel::class);
        $tenants = $user->getTenants($panel);

        expect($tenants)->toHaveCount(2);
    });

    test('company owner with partner code is not restricted', function () {
        $company = Company::factory()->create(['partner_code' => 'PARTNER123']);
        $user = User::factory()->create();
        $user->companies()->attach($company, ['role' => CompanyRoleEnum::Owner]);

        actingAs($user);

        // Owner should not be considered a partner collaborator
        expect($user->isPartnerCollaborator())->toBeFalse();

        // Mock panels
        $userPanel = Mockery::mock(\Filament\Panel::class);
        $userPanel->shouldReceive('getId')->andReturn(FilamentPanel::User->value);

        $adminPanel = Mockery::mock(\Filament\Panel::class);
        $adminPanel->shouldReceive('getId')->andReturn(FilamentPanel::Admin->value);

        // Owner should have access to all panels
        expect($user->canAccessPanel($userPanel))->toBeTrue();
        expect($user->canAccessPanel($adminPanel))->toBeTrue();
    });

    test('company manager with partner code is not restricted', function () {
        $company = Company::factory()->create(['partner_code' => 'PARTNER123']);
        $user = User::factory()->create();
        $user->companies()->attach($company, ['role' => CompanyRoleEnum::Manager]);

        actingAs($user);

        // Manager should not be considered a partner collaborator
        expect($user->isPartnerCollaborator())->toBeFalse();

        // Mock panels
        $userPanel = Mockery::mock(\Filament\Panel::class);
        $userPanel->shouldReceive('getId')->andReturn(FilamentPanel::User->value);

        $adminPanel = Mockery::mock(\Filament\Panel::class);
        $adminPanel->shouldReceive('getId')->andReturn(FilamentPanel::Admin->value);

        // Manager should have access to all panels
        expect($user->canAccessPanel($userPanel))->toBeTrue();
        expect($user->canAccessPanel($adminPanel))->toBeTrue();
    });
});
