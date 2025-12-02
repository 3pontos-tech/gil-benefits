<?php

use App\Models\Users\User;
use Illuminate\Support\Facades\Gate;
use TresPontosTech\Company\Models\Company;
use TresPontosTech\Company\Enums\CompanyRoleEnum;
use TresPontosTech\Appointments\Models\Appointment;
use TresPontosTech\Consultants\Models\Consultant;

beforeEach(function () {
    $this->company = Company::factory()->create();
    $this->owner = User::factory()->create();
    $this->manager = User::factory()->create();
    $this->employee = User::factory()->create();
    $this->partnerCollaborator = User::factory()->partnerCollaborator()->create();
    
    // Attach users to company with roles
    $this->company->users()->attach($this->owner, ['role' => CompanyRoleEnum::Owner]);
    $this->company->users()->attach($this->manager, ['role' => CompanyRoleEnum::Manager]);
    $this->company->users()->attach($this->employee, ['role' => CompanyRoleEnum::Employee]);
});

describe('Panel Access Authorization', function () {
    it('allows owners to access admin panel', function () {
        expect(Gate::forUser($this->owner)->allows('access-admin-panel'))->toBeTrue();
    });

    it('allows managers to access admin panel', function () {
        expect(Gate::forUser($this->manager)->allows('access-admin-panel'))->toBeTrue();
    });

    it('denies employees access to admin panel', function () {
        expect(Gate::forUser($this->employee)->allows('access-admin-panel'))->toBeFalse();
    });

    it('denies partner collaborators access to admin panel', function () {
        expect(Gate::forUser($this->partnerCollaborator)->allows('access-admin-panel'))->toBeFalse();
    });

    it('allows all authenticated users to access user panel', function () {
        expect(Gate::forUser($this->owner)->allows('access-user-panel'))->toBeTrue();
        expect(Gate::forUser($this->manager)->allows('access-user-panel'))->toBeTrue();
        expect(Gate::forUser($this->employee)->allows('access-user-panel'))->toBeTrue();
        expect(Gate::forUser($this->partnerCollaborator)->allows('access-user-panel'))->toBeTrue();
    });

    it('denies partner collaborators access to company panel', function () {
        expect(Gate::forUser($this->partnerCollaborator)->allows('access-company-panel'))->toBeFalse();
    });
});

describe('Company Management Authorization', function () {
    it('allows owners to manage company members', function () {
        expect(Gate::forUser($this->owner)->allows('manage-company-members', $this->company))->toBeTrue();
    });

    it('allows managers to manage company members', function () {
        expect(Gate::forUser($this->manager)->allows('manage-company-members', $this->company))->toBeTrue();
    });

    it('denies employees from managing company members', function () {
        expect(Gate::forUser($this->employee)->allows('manage-company-members', $this->company))->toBeFalse();
    });

    it('allows only owners to manage company settings', function () {
        expect(Gate::forUser($this->owner)->allows('manage-company-settings', $this->company))->toBeTrue();
        expect(Gate::forUser($this->manager)->allows('manage-company-settings', $this->company))->toBeFalse();
        expect(Gate::forUser($this->employee)->allows('manage-company-settings', $this->company))->toBeFalse();
    });

    it('allows owners and managers to view company analytics', function () {
        expect(Gate::forUser($this->owner)->allows('view-company-analytics', $this->company))->toBeTrue();
        expect(Gate::forUser($this->manager)->allows('view-company-analytics', $this->company))->toBeTrue();
        expect(Gate::forUser($this->employee)->allows('view-company-analytics', $this->company))->toBeFalse();
    });
});

describe('Billing and Financial Authorization', function () {
    it('allows only owners to access billing information', function () {
        expect(Gate::forUser($this->owner)->allows('access-billing-information', $this->company))->toBeTrue();
        expect(Gate::forUser($this->manager)->allows('access-billing-information', $this->company))->toBeFalse();
        expect(Gate::forUser($this->employee)->allows('access-billing-information', $this->company))->toBeFalse();
        expect(Gate::forUser($this->partnerCollaborator)->allows('access-billing-information', $this->company))->toBeFalse();
    });

    it('allows only owners to access financial data', function () {
        expect(Gate::forUser($this->owner)->allows('access-financial-data', $this->company))->toBeTrue();
        expect(Gate::forUser($this->manager)->allows('access-financial-data', $this->company))->toBeFalse();
        expect(Gate::forUser($this->partnerCollaborator)->allows('access-financial-data', $this->company))->toBeFalse();
    });

    it('allows only owners to manage subscription billing', function () {
        expect(Gate::forUser($this->owner)->allows('manage-subscription-billing', $this->company))->toBeTrue();
        expect(Gate::forUser($this->manager)->allows('manage-subscription-billing', $this->company))->toBeFalse();
        expect(Gate::forUser($this->partnerCollaborator)->allows('manage-subscription-billing', $this->company))->toBeFalse();
    });
});

describe('Tenant Isolation Authorization', function () {
    it('allows users to access their own company data', function () {
        expect(Gate::forUser($this->owner)->allows('tenant-isolation', $this->company))->toBeTrue();
        expect(Gate::forUser($this->manager)->allows('tenant-isolation', $this->company))->toBeTrue();
        expect(Gate::forUser($this->employee)->allows('tenant-isolation', $this->company))->toBeTrue();
    });

    it('denies users access to other company data', function () {
        $otherCompany = Company::factory()->create();
        
        expect(Gate::forUser($this->owner)->allows('tenant-isolation', $otherCompany))->toBeFalse();
        expect(Gate::forUser($this->manager)->allows('tenant-isolation', $otherCompany))->toBeFalse();
        expect(Gate::forUser($this->employee)->allows('tenant-isolation', $otherCompany))->toBeFalse();
    });

    it('restricts partner collaborators to their partner company only', function () {
        // Set up partner company relationship
        $partnerCompany = Company::factory()->create();
        $this->partnerCollaborator->update(['partner_company_id' => $partnerCompany->id]);
        
        expect(Gate::forUser($this->partnerCollaborator)->allows('tenant-isolation', $partnerCompany))->toBeTrue();
        expect(Gate::forUser($this->partnerCollaborator)->allows('tenant-isolation', $this->company))->toBeFalse();
    });
});

describe('Advanced Authorization Gates', function () {
    it('allows owners to access system administration', function () {
        expect(Gate::forUser($this->owner)->allows('access-system-administration'))->toBeTrue();
        expect(Gate::forUser($this->manager)->allows('access-system-administration'))->toBeFalse();
        expect(Gate::forUser($this->partnerCollaborator)->allows('access-system-administration'))->toBeFalse();
    });

    it('allows owners to manage security settings', function () {
        expect(Gate::forUser($this->owner)->allows('manage-security-settings', $this->company))->toBeTrue();
        expect(Gate::forUser($this->manager)->allows('manage-security-settings', $this->company))->toBeFalse();
        expect(Gate::forUser($this->partnerCollaborator)->allows('manage-security-settings', $this->company))->toBeFalse();
    });

    it('allows owners and managers to manage API access', function () {
        expect(Gate::forUser($this->owner)->allows('manage-api-access', $this->company))->toBeTrue();
        expect(Gate::forUser($this->manager)->allows('manage-api-access', $this->company))->toBeTrue();
        expect(Gate::forUser($this->employee)->allows('manage-api-access', $this->company))->toBeFalse();
        expect(Gate::forUser($this->partnerCollaborator)->allows('manage-api-access', $this->company))->toBeFalse();
    });

    it('restricts partner collaborators from accessing sensitive data', function () {
        expect(Gate::forUser($this->partnerCollaborator)->allows('access-sensitive-data', 'billing'))->toBeFalse();
        expect(Gate::forUser($this->partnerCollaborator)->allows('access-sensitive-data', 'appointments'))->toBeTrue();
        expect(Gate::forUser($this->partnerCollaborator)->allows('access-sensitive-data', 'profile'))->toBeTrue();
    });

    it('allows cross-tenant data access only for owners of both companies', function () {
        $otherCompany = Company::factory()->create();
        $otherCompany->users()->attach($this->owner, ['role' => CompanyRoleEnum::Owner]);
        
        expect(Gate::forUser($this->owner)->allows('access-multi-tenant-data', $this->company, $otherCompany))->toBeTrue();
        expect(Gate::forUser($this->manager)->allows('access-multi-tenant-data', $this->company, $otherCompany))->toBeFalse();
    });

    it('allows owners to manage team permissions', function () {
        expect(Gate::forUser($this->owner)->allows('manage-team-permissions', $this->company, $this->employee))->toBeTrue();
        expect(Gate::forUser($this->manager)->allows('manage-team-permissions', $this->company, $this->employee))->toBeTrue();
        expect(Gate::forUser($this->employee)->allows('manage-team-permissions', $this->company, $this->manager))->toBeFalse();
    });

    it('prevents users from modifying their own permissions', function () {
        expect(Gate::forUser($this->owner)->allows('manage-team-permissions', $this->company, $this->owner))->toBeFalse();
        expect(Gate::forUser($this->manager)->allows('manage-team-permissions', $this->company, $this->manager))->toBeFalse();
    });

    it('prevents managers from modifying owner permissions', function () {
        expect(Gate::forUser($this->manager)->allows('manage-team-permissions', $this->company, $this->owner))->toBeFalse();
    });
});

describe('Bulk Operations Authorization', function () {
    it('denies partner collaborators from performing bulk operations', function () {
        expect(Gate::forUser($this->partnerCollaborator)->allows('bulk-operations', 'update', $this->company))->toBeFalse();
        expect(Gate::forUser($this->partnerCollaborator)->allows('bulk-operations', 'delete', $this->company))->toBeFalse();
    });

    it('requires owner role for destructive bulk operations', function () {
        expect(Gate::forUser($this->owner)->allows('bulk-operations', 'delete', $this->company))->toBeTrue();
        expect(Gate::forUser($this->manager)->allows('bulk-operations', 'delete', $this->company))->toBeFalse();
        expect(Gate::forUser($this->employee)->allows('bulk-operations', 'delete', $this->company))->toBeFalse();
    });

    it('allows managers to perform non-destructive bulk operations', function () {
        expect(Gate::forUser($this->manager)->allows('bulk-operations', 'update', $this->company))->toBeTrue();
        expect(Gate::forUser($this->manager)->allows('bulk-operations', 'export', $this->company))->toBeTrue();
    });
});

describe('Analytics and Reporting Authorization', function () {
    it('allows different analytics access levels based on roles', function () {
        expect(Gate::forUser($this->owner)->allows('access-advanced-analytics', $this->company, 'financial'))->toBeTrue();
        expect(Gate::forUser($this->manager)->allows('access-advanced-analytics', $this->company, 'advanced'))->toBeTrue();
        expect(Gate::forUser($this->employee)->allows('access-advanced-analytics', $this->company, 'basic'))->toBeTrue();
        
        expect(Gate::forUser($this->manager)->allows('access-advanced-analytics', $this->company, 'financial'))->toBeFalse();
        expect(Gate::forUser($this->employee)->allows('access-advanced-analytics', $this->company, 'advanced'))->toBeFalse();
    });

    it('restricts partner collaborators to basic analytics only', function () {
        expect(Gate::forUser($this->partnerCollaborator)->allows('access-advanced-analytics', $this->company, 'basic'))->toBeTrue();
        expect(Gate::forUser($this->partnerCollaborator)->allows('access-advanced-analytics', $this->company, 'advanced'))->toBeFalse();
    });

    it('allows owners and managers to access reporting analytics', function () {
        expect(Gate::forUser($this->owner)->allows('access-reporting-analytics', $this->company))->toBeTrue();
        expect(Gate::forUser($this->manager)->allows('access-reporting-analytics', $this->company))->toBeTrue();
        expect(Gate::forUser($this->employee)->allows('access-reporting-analytics', $this->company))->toBeFalse();
    });
});

describe('Emergency and Special Access Authorization', function () {
    it('allows owners to use emergency access', function () {
        expect(Gate::forUser($this->owner)->allows('emergency-access', 'System maintenance required'))->toBeTrue();
        expect(Gate::forUser($this->manager)->allows('emergency-access', 'System maintenance required'))->toBeFalse();
        expect(Gate::forUser($this->partnerCollaborator)->allows('emergency-access', 'System maintenance required'))->toBeFalse();
    });

    it('allows owners to impersonate other users', function () {
        expect(Gate::forUser($this->owner)->allows('impersonate-user', $this->employee))->toBeTrue();
        expect(Gate::forUser($this->manager)->allows('impersonate-user', $this->employee))->toBeFalse();
        expect(Gate::forUser($this->partnerCollaborator)->allows('impersonate-user', $this->employee))->toBeFalse();
    });

    it('prevents users from impersonating themselves', function () {
        expect(Gate::forUser($this->owner)->allows('impersonate-user', $this->owner))->toBeFalse();
    });
});

describe('Data Export and Privacy Authorization', function () {
    it('allows different export permissions based on data sensitivity', function () {
        expect(Gate::forUser($this->owner)->allows('perform-data-export', $this->company, 'bulk', ['billing', 'financial']))->toBeTrue();
        expect(Gate::forUser($this->manager)->allows('perform-data-export', $this->company, 'bulk', ['appointments']))->toBeTrue();
        expect(Gate::forUser($this->manager)->allows('perform-data-export', $this->company, 'bulk', ['billing']))->toBeFalse();
        expect(Gate::forUser($this->partnerCollaborator)->allows('perform-data-export', $this->company, 'bulk', []))->toBeFalse();
    });

    it('allows only owners to manage data privacy settings', function () {
        expect(Gate::forUser($this->owner)->allows('manage-data-privacy-settings', $this->company))->toBeTrue();
        expect(Gate::forUser($this->manager)->allows('manage-data-privacy-settings', $this->company))->toBeFalse();
        expect(Gate::forUser($this->partnerCollaborator)->allows('manage-data-privacy-settings', $this->company))->toBeFalse();
    });

    it('allows only owners to manage data export policies', function () {
        expect(Gate::forUser($this->owner)->allows('manage-data-export-policies', $this->company))->toBeTrue();
        expect(Gate::forUser($this->manager)->allows('manage-data-export-policies', $this->company))->toBeFalse();
    });
});

describe('Historical Data Access Authorization', function () {
    it('allows different historical data access based on roles', function () {
        expect(Gate::forUser($this->owner)->allows('access-historical-data', $this->company, 36))->toBeTrue(); // Unlimited
        expect(Gate::forUser($this->manager)->allows('access-historical-data', $this->company, 24))->toBeTrue(); // 24 months
        expect(Gate::forUser($this->manager)->allows('access-historical-data', $this->company, 36))->toBeFalse(); // Over limit
        expect(Gate::forUser($this->employee)->allows('access-historical-data', $this->company, 6))->toBeTrue(); // 6 months
        expect(Gate::forUser($this->employee)->allows('access-historical-data', $this->company, 12))->toBeFalse(); // Over limit
    });

    it('restricts partner collaborators to 3 months of historical data', function () {
        expect(Gate::forUser($this->partnerCollaborator)->allows('access-historical-data', $this->company, 3))->toBeTrue();
        expect(Gate::forUser($this->partnerCollaborator)->allows('access-historical-data', $this->company, 6))->toBeFalse();
    });
});