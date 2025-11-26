<?php

use App\Models\Users\User;
use App\Policies\PartnerCollaboratorPolicy;
use Illuminate\Auth\Access\Response;
use TresPontosTech\Company\Enums\CompanyRoleEnum;
use TresPontosTech\Company\Models\Company;

describe('PartnerCollaboratorPolicy', function () {
    
    beforeEach(function () {
        $this->policy = new PartnerCollaboratorPolicy();
    });

    test('accessAdminPanel denies access for partner collaborators', function () {
        $company = Company::factory()->create(['partner_code' => 'PARTNER123']);
        $user = User::factory()->create();
        $user->companies()->attach($company, ['role' => CompanyRoleEnum::Employee]);
        
        $response = $this->policy->accessAdminPanel($user);
        
        expect($response)->toBeInstanceOf(Response::class);
        expect($response->denied())->toBeTrue();
        expect($response->message())->toBe('Partner collaborators cannot access the admin panel.');
    });

    test('accessAdminPanel allows access for non-partner collaborators', function () {
        $company = Company::factory()->create(['partner_code' => null]);
        $user = User::factory()->create();
        $user->companies()->attach($company, ['role' => CompanyRoleEnum::Employee]);
        
        $response = $this->policy->accessAdminPanel($user);
        
        expect($response)->toBeInstanceOf(Response::class);
        expect($response->allowed())->toBeTrue();
    });

    test('accessCompanyPanel denies access for partner collaborators', function () {
        $company = Company::factory()->create(['partner_code' => 'PARTNER123']);
        $user = User::factory()->create();
        $user->companies()->attach($company, ['role' => CompanyRoleEnum::Employee]);
        
        $response = $this->policy->accessCompanyPanel($user);
        
        expect($response)->toBeInstanceOf(Response::class);
        expect($response->denied())->toBeTrue();
        expect($response->message())->toBe('Partner collaborators cannot access the company panel.');
    });

    test('accessCompanyPanel allows access for non-partner collaborators', function () {
        $company = Company::factory()->create(['partner_code' => null]);
        $user = User::factory()->create();
        $user->companies()->attach($company, ['role' => CompanyRoleEnum::Employee]);
        
        $response = $this->policy->accessCompanyPanel($user);
        
        expect($response)->toBeInstanceOf(Response::class);
        expect($response->allowed())->toBeTrue();
    });

    test('accessConsultantPanel denies access for partner collaborators', function () {
        $company = Company::factory()->create(['partner_code' => 'PARTNER123']);
        $user = User::factory()->create();
        $user->companies()->attach($company, ['role' => CompanyRoleEnum::Employee]);
        
        $response = $this->policy->accessConsultantPanel($user);
        
        expect($response)->toBeInstanceOf(Response::class);
        expect($response->denied())->toBeTrue();
        expect($response->message())->toBe('Partner collaborators cannot access the consultant panel.');
    });

    test('accessConsultantPanel allows access for non-partner collaborators', function () {
        $company = Company::factory()->create(['partner_code' => null]);
        $user = User::factory()->create();
        $user->companies()->attach($company, ['role' => CompanyRoleEnum::Employee]);
        
        $response = $this->policy->accessConsultantPanel($user);
        
        expect($response)->toBeInstanceOf(Response::class);
        expect($response->allowed())->toBeTrue();
    });

    test('accessGuestPanel denies access for partner collaborators', function () {
        $company = Company::factory()->create(['partner_code' => 'PARTNER123']);
        $user = User::factory()->create();
        $user->companies()->attach($company, ['role' => CompanyRoleEnum::Employee]);
        
        $response = $this->policy->accessGuestPanel($user);
        
        expect($response)->toBeInstanceOf(Response::class);
        expect($response->denied())->toBeTrue();
        expect($response->message())->toBe('Partner collaborators cannot access the guest panel.');
    });

    test('accessGuestPanel allows access for non-partner collaborators', function () {
        $company = Company::factory()->create(['partner_code' => null]);
        $user = User::factory()->create();
        $user->companies()->attach($company, ['role' => CompanyRoleEnum::Employee]);
        
        $response = $this->policy->accessGuestPanel($user);
        
        expect($response)->toBeInstanceOf(Response::class);
        expect($response->allowed())->toBeTrue();
    });

    test('accessUserPanel allows access for all users', function () {
        $company = Company::factory()->create(['partner_code' => 'PARTNER123']);
        $user = User::factory()->create();
        $user->companies()->attach($company, ['role' => CompanyRoleEnum::Employee]);
        
        $response = $this->policy->accessUserPanel($user);
        
        expect($response)->toBeInstanceOf(Response::class);
        expect($response->allowed())->toBeTrue();
    });

    test('accessTenantData denies access to other company data for partner collaborators', function () {
        $partnerCompany = Company::factory()->create(['partner_code' => 'PARTNER123']);
        $otherCompany = Company::factory()->create(['partner_code' => 'OTHER456']);
        $user = User::factory()->create();
        $user->companies()->attach($partnerCompany, ['role' => CompanyRoleEnum::Employee]);
        
        $response = $this->policy->accessTenantData($user, $otherCompany);
        
        expect($response)->toBeInstanceOf(Response::class);
        expect($response->denied())->toBeTrue();
        expect($response->message())->toBe('Partner collaborators can only access their own company data.');
    });

    test('accessTenantData allows access to own company data for partner collaborators', function () {
        $partnerCompany = Company::factory()->create(['partner_code' => 'PARTNER123']);
        $user = User::factory()->create();
        $user->companies()->attach($partnerCompany, ['role' => CompanyRoleEnum::Employee]);
        
        $response = $this->policy->accessTenantData($user, $partnerCompany);
        
        expect($response)->toBeInstanceOf(Response::class);
        expect($response->allowed())->toBeTrue();
    });

    test('accessTenantData allows access for non-partner collaborators', function () {
        $company = Company::factory()->create(['partner_code' => null]);
        $user = User::factory()->create();
        $user->companies()->attach($company, ['role' => CompanyRoleEnum::Employee]);
        
        $response = $this->policy->accessTenantData($user, $company);
        
        expect($response)->toBeInstanceOf(Response::class);
        expect($response->allowed())->toBeTrue();
    });
});