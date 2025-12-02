<?php

use App\Filament\FilamentPanel;
use App\Models\Users\User;
use Filament\Panel;
use TresPontosTech\Company\Enums\CompanyRoleEnum;
use TresPontosTech\Company\Models\Company;

describe('User Access Control', function () {

    test('isPartnerCollaborator returns true for employee of company with partner code', function () {
        $company = Company::factory()->create(['partner_code' => 'PARTNER123']);
        $user = User::factory()->create();

        // Attach user as employee to company with partner code
        $user->companies()->attach($company, ['role' => CompanyRoleEnum::Employee]);

        expect($user->isPartnerCollaborator())->toBeTrue();
    });

    test('isPartnerCollaborator returns false for employee of company without partner code', function () {
        $company = Company::factory()->create(['partner_code' => null]);
        $user = User::factory()->create();

        // Attach user as employee to company without partner code
        $user->companies()->attach($company, ['role' => CompanyRoleEnum::Employee]);

        expect($user->isPartnerCollaborator())->toBeFalse();
    });

    test('isPartnerCollaborator returns false for owner of company with partner code', function () {
        $company = Company::factory()->create(['partner_code' => 'PARTNER123']);
        $user = User::factory()->create();

        // Attach user as owner to company with partner code
        $user->companies()->attach($company, ['role' => CompanyRoleEnum::Owner]);

        expect($user->isPartnerCollaborator())->toBeFalse();
    });

    test('isPartnerCollaborator returns false for manager of company with partner code', function () {
        $company = Company::factory()->create(['partner_code' => 'PARTNER123']);
        $user = User::factory()->create();

        // Attach user as manager to company with partner code
        $user->companies()->attach($company, ['role' => CompanyRoleEnum::Manager]);

        expect($user->isPartnerCollaborator())->toBeFalse();
    });

    test('getPartnerCompany returns correct company for partner collaborator', function () {
        $company = Company::factory()->create(['partner_code' => 'PARTNER123']);
        $user = User::factory()->create();

        $user->companies()->attach($company, ['role' => CompanyRoleEnum::Employee]);

        $partnerCompany = $user->getPartnerCompany();

        expect($partnerCompany)->not->toBeNull();
        expect($partnerCompany->id)->toBe($company->id);
        expect($partnerCompany->partner_code)->toBe('PARTNER123');
    });

    test('getPartnerCompany returns null for non-partner collaborator', function () {
        $company = Company::factory()->create(['partner_code' => null]);
        $user = User::factory()->create();

        $user->companies()->attach($company, ['role' => CompanyRoleEnum::Employee]);

        expect($user->getPartnerCompany())->toBeNull();
    });

    test('canAccessPanel allows only User Panel for partner collaborators', function () {
        $company = Company::factory()->create(['partner_code' => 'PARTNER123']);
        $user = User::factory()->create();

        $user->companies()->attach($company, ['role' => CompanyRoleEnum::Employee]);

        // Mock panels
        $userPanel = Mockery::mock(Panel::class);
        $userPanel->shouldReceive('getId')->andReturn(FilamentPanel::User->value);

        $adminPanel = Mockery::mock(Panel::class);
        $adminPanel->shouldReceive('getId')->andReturn(FilamentPanel::Admin->value);

        $companyPanel = Mockery::mock(Panel::class);
        $companyPanel->shouldReceive('getId')->andReturn(FilamentPanel::Company->value);

        $consultantPanel = Mockery::mock(Panel::class);
        $consultantPanel->shouldReceive('getId')->andReturn(FilamentPanel::Consultant->value);

        $guestPanel = Mockery::mock(Panel::class);
        $guestPanel->shouldReceive('getId')->andReturn(FilamentPanel::Guest->value);

        // Partner collaborator should only access User Panel
        expect($user->canAccessPanel($userPanel))->toBeTrue();
        expect($user->canAccessPanel($adminPanel))->toBeFalse();
        expect($user->canAccessPanel($companyPanel))->toBeFalse();
        expect($user->canAccessPanel($consultantPanel))->toBeFalse();
        expect($user->canAccessPanel($guestPanel))->toBeFalse();
    });

    test('canAccessPanel allows all panels for non-partner collaborators', function () {
        $company = Company::factory()->create(['partner_code' => null]);
        $user = User::factory()->create();

        $user->companies()->attach($company, ['role' => CompanyRoleEnum::Employee]);

        // Mock panels
        $userPanel = Mockery::mock(Panel::class);
        $userPanel->shouldReceive('getId')->andReturn(FilamentPanel::User->value);

        $adminPanel = Mockery::mock(Panel::class);
        $adminPanel->shouldReceive('getId')->andReturn(FilamentPanel::Admin->value);

        // Non-partner collaborator should access all panels
        expect($user->canAccessPanel($userPanel))->toBeTrue();
        expect($user->canAccessPanel($adminPanel))->toBeTrue();
    });

    test('getTenants returns only partner company for partner collaborators', function () {
        $partnerCompany = Company::factory()->create(['partner_code' => 'PARTNER123']);
        $otherCompany = Company::factory()->create(['partner_code' => null]);
        $user = User::factory()->create();

        // Attach user to both companies
        $user->companies()->attach($partnerCompany, ['role' => CompanyRoleEnum::Employee]);
        $user->companies()->attach($otherCompany, ['role' => CompanyRoleEnum::Employee]);

        $panel = Mockery::mock(Panel::class);
        $tenants = $user->getTenants($panel);

        expect($tenants)->toHaveCount(1);
        expect($tenants->first()->id)->toBe($partnerCompany->id);
    });

    test('getTenants returns all companies for non-partner collaborators', function () {
        $company1 = Company::factory()->create(['partner_code' => null]);
        $company2 = Company::factory()->create(['partner_code' => null]);
        $user = User::factory()->create();

        $user->companies()->attach($company1, ['role' => CompanyRoleEnum::Employee]);
        $user->companies()->attach($company2, ['role' => CompanyRoleEnum::Employee]);

        $panel = Mockery::mock(Panel::class);
        $tenants = $user->getTenants($panel);

        expect($tenants)->toHaveCount(2);
    });

    test('canAccessTenant restricts access for partner collaborators', function () {
        $partnerCompany = Company::factory()->create(['partner_code' => 'PARTNER123']);
        $otherCompany = Company::factory()->create(['partner_code' => 'OTHER456']);
        $user = User::factory()->create();

        $user->companies()->attach($partnerCompany, ['role' => CompanyRoleEnum::Employee]);

        expect($user->canAccessTenant($partnerCompany))->toBeTrue();
        expect($user->canAccessTenant($otherCompany))->toBeFalse();
    });

    test('canAccessTenant allows access to all associated companies for non-partner collaborators', function () {
        $company1 = Company::factory()->create(['partner_code' => null]);
        $company2 = Company::factory()->create(['partner_code' => null]);
        $user = User::factory()->create();

        $user->companies()->attach($company1, ['role' => CompanyRoleEnum::Employee]);
        $user->companies()->attach($company2, ['role' => CompanyRoleEnum::Employee]);

        expect($user->canAccessTenant($company1))->toBeTrue();
        expect($user->canAccessTenant($company2))->toBeTrue();
    });
});
