<?php

use App\Models\Users\User;
use Illuminate\Support\Facades\Gate;
use TresPontosTech\Company\Enums\CompanyRoleEnum;
use TresPontosTech\Company\Models\Company;

beforeEach(function () {
    // Create test companies
    $this->company1 = Company::factory()->create(['name' => 'Test Company 1']);
    $this->company2 = Company::factory()->create(['name' => 'Test Company 2']);

    // Create users with different roles
    $this->owner = User::factory()->create(['email' => 'owner@test.com']);
    $this->manager = User::factory()->create(['email' => 'manager@test.com']);
    $this->employee = User::factory()->create(['email' => 'employee@test.com']);
    $this->partnerCollaborator = User::factory()->create([
        'email' => 'partner@test.com',
    ]);

    // Make company2 a partner company by adding a partner_code
    $this->company2->update(['partner_code' => 'PARTNER123']);

    // Attach users to companies with roles
    $this->owner->companies()->attach($this->company1, ['role' => CompanyRoleEnum::Owner]);
    $this->manager->companies()->attach($this->company1, ['role' => CompanyRoleEnum::Manager]);
    $this->employee->companies()->attach($this->company1, ['role' => CompanyRoleEnum::Employee]);
    $this->partnerCollaborator->companies()->attach($this->company2, ['role' => CompanyRoleEnum::Employee]);
});

describe('Enhanced Authorization Gates', function () {
    it('allows system administration access only to owners', function () {
        expect(Gate::forUser($this->owner)->allows('access-system-administration'))->toBeTrue();
        expect(Gate::forUser($this->manager)->allows('access-system-administration'))->toBeFalse();
        expect(Gate::forUser($this->employee)->allows('access-system-administration'))->toBeFalse();
        expect(Gate::forUser($this->partnerCollaborator)->allows('access-system-administration'))->toBeFalse();
    });

    it('allows security settings management only to owners', function () {
        expect(Gate::forUser($this->owner)->allows('manage-security-settings', $this->company1))->toBeTrue();
        expect(Gate::forUser($this->manager)->allows('manage-security-settings', $this->company1))->toBeFalse();
        expect(Gate::forUser($this->employee)->allows('manage-security-settings', $this->company1))->toBeFalse();
        expect(Gate::forUser($this->partnerCollaborator)->allows('manage-security-settings', $this->company2))->toBeFalse();
    });

    it('allows financial data access only to owners', function () {
        expect(Gate::forUser($this->owner)->allows('access-financial-data', $this->company1))->toBeTrue();
        expect(Gate::forUser($this->manager)->allows('access-financial-data', $this->company1))->toBeFalse();
        expect(Gate::forUser($this->employee)->allows('access-financial-data', $this->company1))->toBeFalse();
        expect(Gate::forUser($this->partnerCollaborator)->allows('access-financial-data', $this->company2))->toBeFalse();
    });

    it('allows API access management to owners and managers', function () {
        expect(Gate::forUser($this->owner)->allows('manage-api-access', $this->company1))->toBeTrue();
        expect(Gate::forUser($this->manager)->allows('manage-api-access', $this->company1))->toBeTrue();
        expect(Gate::forUser($this->employee)->allows('manage-api-access', $this->company1))->toBeFalse();
        expect(Gate::forUser($this->partnerCollaborator)->allows('manage-api-access', $this->company2))->toBeFalse();
    });

    it('allows reporting analytics access with proper restrictions', function () {
        expect(Gate::forUser($this->owner)->allows('access-reporting-analytics', $this->company1))->toBeTrue();
        expect(Gate::forUser($this->manager)->allows('access-reporting-analytics', $this->company1))->toBeTrue();
        expect(Gate::forUser($this->employee)->allows('access-reporting-analytics', $this->company1))->toBeFalse();
        
        // Partner collaborators can only access their partner company
        expect(Gate::forUser($this->partnerCollaborator)->allows('access-reporting-analytics', $this->company2))->toBeTrue();
        expect(Gate::forUser($this->partnerCollaborator)->allows('access-reporting-analytics', $this->company1))->toBeFalse();
    });

    it('restricts webhooks and integrations management to owners', function () {
        expect(Gate::forUser($this->owner)->allows('manage-webhooks-integrations', $this->company1))->toBeTrue();
        expect(Gate::forUser($this->manager)->allows('manage-webhooks-integrations', $this->company1))->toBeFalse();
        expect(Gate::forUser($this->employee)->allows('manage-webhooks-integrations', $this->company1))->toBeFalse();
        expect(Gate::forUser($this->partnerCollaborator)->allows('manage-webhooks-integrations', $this->company2))->toBeFalse();
    });

    it('allows audit trail access to owners and managers', function () {
        expect(Gate::forUser($this->owner)->allows('access-audit-trail', $this->company1))->toBeTrue();
        expect(Gate::forUser($this->manager)->allows('access-audit-trail', $this->company1))->toBeTrue();
        expect(Gate::forUser($this->employee)->allows('access-audit-trail', $this->company1))->toBeFalse();
        expect(Gate::forUser($this->partnerCollaborator)->allows('access-audit-trail', $this->company2))->toBeFalse();
    });

    it('restricts data retention management to owners', function () {
        expect(Gate::forUser($this->owner)->allows('manage-data-retention', $this->company1))->toBeTrue();
        expect(Gate::forUser($this->manager)->allows('manage-data-retention', $this->company1))->toBeFalse();
        expect(Gate::forUser($this->employee)->allows('manage-data-retention', $this->company1))->toBeFalse();
        expect(Gate::forUser($this->partnerCollaborator)->allows('manage-data-retention', $this->company2))->toBeFalse();
    });

    it('allows emergency access only to owners and logs the attempt', function () {
        expect(Gate::forUser($this->owner)->allows('emergency-access', 'System maintenance'))->toBeTrue();
        expect(Gate::forUser($this->manager)->allows('emergency-access', 'System maintenance'))->toBeFalse();
        expect(Gate::forUser($this->employee)->allows('emergency-access', 'System maintenance'))->toBeFalse();
        expect(Gate::forUser($this->partnerCollaborator)->allows('emergency-access', 'System maintenance'))->toBeFalse();

        // Check that audit log was created
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $this->owner->id,
            'action' => 'emergency_access',
            'granted' => true,
        ]);
    });

    it('allows user impersonation only to owners and logs the attempt', function () {
        expect(Gate::forUser($this->owner)->allows('impersonate-user', $this->employee))->toBeTrue();
        expect(Gate::forUser($this->manager)->allows('impersonate-user', $this->employee))->toBeFalse();
        expect(Gate::forUser($this->employee)->allows('impersonate-user', $this->manager))->toBeFalse();
        expect(Gate::forUser($this->partnerCollaborator)->allows('impersonate-user', $this->employee))->toBeFalse();

        // Users cannot impersonate themselves
        expect(Gate::forUser($this->owner)->allows('impersonate-user', $this->owner))->toBeFalse();

        // Check that audit log was created
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $this->owner->id,
            'action' => 'impersonate_user',
            'granted' => true,
        ]);
    });

    it('handles bulk operations with proper role restrictions', function () {
        // Non-destructive operations
        expect(Gate::forUser($this->owner)->allows('bulk-operations', 'export', $this->company1))->toBeTrue();
        expect(Gate::forUser($this->manager)->allows('bulk-operations', 'export', $this->company1))->toBeTrue();
        expect(Gate::forUser($this->employee)->allows('bulk-operations', 'export', $this->company1))->toBeFalse();
        expect(Gate::forUser($this->partnerCollaborator)->allows('bulk-operations', 'export', $this->company2))->toBeFalse();

        // Destructive operations (should require owner role)
        expect(Gate::forUser($this->owner)->allows('bulk-operations', 'delete', $this->company1))->toBeTrue();
        expect(Gate::forUser($this->manager)->allows('bulk-operations', 'delete', $this->company1))->toBeFalse();
        expect(Gate::forUser($this->employee)->allows('bulk-operations', 'delete', $this->company1))->toBeFalse();
        expect(Gate::forUser($this->partnerCollaborator)->allows('bulk-operations', 'delete', $this->company2))->toBeFalse();
    });
});

describe('Policy-Based Authorization', function () {
    it('enforces company policy correctly', function () {
        // Users can view companies they belong to
        expect(Gate::forUser($this->owner)->allows('view', $this->company1))->toBeTrue();
        expect(Gate::forUser($this->manager)->allows('view', $this->company1))->toBeTrue();
        expect(Gate::forUser($this->employee)->allows('view', $this->company1))->toBeTrue();
        
        // Partner collaborators can only view their partner company
        expect(Gate::forUser($this->partnerCollaborator)->allows('view', $this->company2))->toBeTrue();
        expect(Gate::forUser($this->partnerCollaborator)->allows('view', $this->company1))->toBeFalse();

        // Only owners can update companies
        expect(Gate::forUser($this->owner)->allows('update', $this->company1))->toBeTrue();
        expect(Gate::forUser($this->manager)->allows('update', $this->company1))->toBeFalse();
        expect(Gate::forUser($this->partnerCollaborator)->allows('update', $this->company2))->toBeFalse();
    });

    it('enforces billing policy correctly', function () {
        $plan = \TresPontosTech\Billing\Core\Models\Plan::factory()->create();

        // All users can view plans
        expect(Gate::forUser($this->owner)->allows('view', $plan))->toBeTrue();
        expect(Gate::forUser($this->manager)->allows('view', $plan))->toBeTrue();
        expect(Gate::forUser($this->employee)->allows('view', $plan))->toBeTrue();
        expect(Gate::forUser($this->partnerCollaborator)->allows('view', $plan))->toBeTrue();

        // Only owners can create/update/delete plans
        expect(Gate::forUser($this->owner)->allows('create', \TresPontosTech\Billing\Core\Models\Plan::class))->toBeTrue();
        expect(Gate::forUser($this->manager)->allows('create', \TresPontosTech\Billing\Core\Models\Plan::class))->toBeFalse();
        expect(Gate::forUser($this->partnerCollaborator)->allows('create', \TresPontosTech\Billing\Core\Models\Plan::class))->toBeFalse();

        expect(Gate::forUser($this->owner)->allows('update', $plan))->toBeTrue();
        expect(Gate::forUser($this->manager)->allows('update', $plan))->toBeFalse();
        expect(Gate::forUser($this->partnerCollaborator)->allows('update', $plan))->toBeFalse();
    });
});

describe('Audit Logging', function () {
    it('logs all authorization decisions', function () {
        // Perform some authorization checks
        Gate::forUser($this->owner)->allows('access-admin-panel');
        Gate::forUser($this->employee)->allows('access-admin-panel');
        Gate::forUser($this->partnerCollaborator)->allows('access-company-panel');

        // Check that audit logs were created
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $this->owner->id,
            'event_type' => 'authorization_decision',
            'granted' => true,
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $this->employee->id,
            'event_type' => 'authorization_decision',
            'granted' => false,
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $this->partnerCollaborator->id,
            'event_type' => 'authorization_decision',
            'granted' => false,
        ]);
    });

    it('generates authorization reports correctly', function () {
        // Perform several authorization checks
        Gate::forUser($this->owner)->allows('access-admin-panel');
        Gate::forUser($this->manager)->allows('access-company-panel');
        Gate::forUser($this->employee)->allows('access-admin-panel'); // Should be denied
        Gate::forUser($this->partnerCollaborator)->allows('access-admin-panel'); // Should be denied

        $auditService = app(\App\Services\AuthorizationAuditService::class);
        $report = $auditService->generateAuthorizationReport(now()->subHour(), now());

        expect($report['total_decisions'])->toBeGreaterThan(0);
        expect($report['granted_decisions'])->toBeGreaterThan(0);
        expect($report['denied_decisions'])->toBeGreaterThan(0);
        expect($report)->toHaveKeys([
            'period',
            'total_decisions',
            'granted_decisions',
            'denied_decisions',
            'actions_summary',
            'users_summary',
            'partner_collaborator_activity',
            'security_incidents',
        ]);
    });

    it('detects suspicious authorization patterns', function () {
        // Simulate multiple failed authorization attempts
        for ($i = 0; $i < 25; $i++) {
            Gate::forUser($this->employee)->allows('access-admin-panel');
        }

        $auditService = app(\App\Services\AuthorizationAuditService::class);
        $suspiciousActivity = $auditService->detectSuspiciousActivity($this->employee);

        // Should detect high denial rate (threshold is 20 in the service)
        expect($suspiciousActivity['denied_requests'])->toBe(25);
        expect($suspiciousActivity['risk_level'])->toBeIn(['medium', 'high']);
        
        if ($suspiciousActivity['risk_level'] === 'high') {
            expect($suspiciousActivity['suspicious_patterns'])->not->toBeEmpty();
            $patterns = collect($suspiciousActivity['suspicious_patterns'])->pluck('type');
            expect($patterns)->toContain('high_denial_rate');
        }
    });
});

describe('Authorization System Integration', function () {
    it('correctly identifies partner collaborators', function () {
        expect($this->partnerCollaborator->isPartnerCollaborator())->toBeTrue();
        expect($this->owner->isPartnerCollaborator())->toBeFalse();
        expect($this->manager->isPartnerCollaborator())->toBeFalse();
        expect($this->employee->isPartnerCollaborator())->toBeFalse();
    });

    it('correctly identifies partner company', function () {
        $partnerCompany = $this->partnerCollaborator->getPartnerCompany();
        expect($partnerCompany)->not->toBeNull();
        expect($partnerCompany->id)->toBe($this->company2->id);
        
        expect($this->owner->getPartnerCompany())->toBeNull();
    });

    it('enforces tenant isolation for partner collaborators', function () {
        expect(Gate::forUser($this->partnerCollaborator)->allows('tenant-isolation', $this->company2))->toBeTrue();
        expect(Gate::forUser($this->partnerCollaborator)->allows('tenant-isolation', $this->company1))->toBeFalse();
    });
});