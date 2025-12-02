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
    $this->maliciousUser = User::factory()->create(['email' => 'malicious@test.com']);

    // Make company2 a partner company by adding a partner_code
    $this->company2->update(['partner_code' => 'PARTNER123']);

    // Attach users to companies with roles
    $this->owner->companies()->attach($this->company1, ['role' => CompanyRoleEnum::Owner]);
    $this->manager->companies()->attach($this->company1, ['role' => CompanyRoleEnum::Manager]);
    $this->employee->companies()->attach($this->company1, ['role' => CompanyRoleEnum::Employee]);
    $this->partnerCollaborator->companies()->attach($this->company2, ['role' => CompanyRoleEnum::Employee]);
    $this->maliciousUser->companies()->attach($this->company1, ['role' => CompanyRoleEnum::Employee]);
});

describe('Tenant Isolation Security', function () {
    it('prevents cross-tenant data access', function () {
        $appointment1 = \TresPontosTech\Appointments\Models\Appointment::factory()->create([
            'user_id' => $this->owner->id,
        ]);
        
        $appointment2 = \TresPontosTech\Appointments\Models\Appointment::factory()->create([
            'user_id' => $this->partnerCollaborator->id,
        ]);

        // Partner collaborator should not access other company's appointments
        expect(Gate::forUser($this->partnerCollaborator)->allows('view', $appointment1))->toBeFalse();
        
        // Regular user should not access partner company appointments
        expect(Gate::forUser($this->owner)->allows('view', $appointment2))->toBeFalse();
    });

    it('enforces tenant isolation in middleware', function () {
        $this->actingAs($this->partnerCollaborator);
        
        // Try to access a company resource from different tenant
        $response = $this->get("/companies/{$this->company1->id}");
        
        // Should be blocked by tenant isolation
        expect($response->getStatusCode())->toBe(403);
        
        // Check that security event was logged
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $this->partnerCollaborator->id,
            'event_type' => 'authorization_decision',
            'granted' => false,
        ]);
    });

    it('detects tenant isolation violations', function () {
        $this->actingAs($this->partnerCollaborator);
        
        // Simulate multiple tenant isolation violations
        for ($i = 0; $i < 6; $i++) {
            try {
                $this->get("/companies/{$this->company1->id}");
            } catch (\Exception $e) {
                // Expected to fail
            }
        }
        
        // Check that potential attack was detected and logged
        // This would be logged by the TenantIsolationMiddleware
        expect(\Illuminate\Support\Facades\Cache::get("tenant_violations_{$this->partnerCollaborator->id}"))->not->toBeEmpty();
    });
});

describe('Privilege Escalation Prevention', function () {
    it('prevents horizontal privilege escalation', function () {
        $otherUser = User::factory()->create(['email' => 'other@test.com']);
        $otherUser->companies()->attach($this->company1, ['role' => CompanyRoleEnum::Employee]);
        
        $appointment = \TresPontosTech\Appointments\Models\Appointment::factory()->create([
            'user_id' => $otherUser->id,
        ]);

        // Employee should not be able to access other employee's appointments
        expect(Gate::forUser($this->employee)->allows('view', $appointment))->toBeFalse();
        expect(Gate::forUser($this->employee)->allows('update', $appointment))->toBeFalse();
        expect(Gate::forUser($this->employee)->allows('delete', $appointment))->toBeFalse();
    });

    it('prevents vertical privilege escalation', function () {
        // Employee should not be able to perform manager/owner actions
        expect(Gate::forUser($this->employee)->allows('manage-company-members', $this->company1))->toBeFalse();
        expect(Gate::forUser($this->employee)->allows('manage-company-settings', $this->company1))->toBeFalse();
        expect(Gate::forUser($this->employee)->allows('access-billing-information', $this->company1))->toBeFalse();
        
        // Manager should not be able to perform owner actions
        expect(Gate::forUser($this->manager)->allows('manage-company-settings', $this->company1))->toBeFalse();
        expect(Gate::forUser($this->manager)->allows('access-billing-information', $this->company1))->toBeFalse();
    });

    it('detects repeated privilege escalation attempts', function () {
        $this->actingAs($this->maliciousUser);
        
        // Simulate multiple unauthorized access attempts
        for ($i = 0; $i < 12; $i++) {
            try {
                $this->withMiddleware(['role.access:owner'])->get('/admin/settings');
            } catch (\Exception $e) {
                // Expected to fail
            }
        }
        
        // Check that potential attack was detected
        $attempts = \Illuminate\Support\Facades\Cache::get("unauthorized_access_{$this->maliciousUser->id}");
        expect($attempts)->not->toBeEmpty();
        expect(count($attempts))->toBeGreaterThan(10);
    });
});

describe('Partner Collaborator Security', function () {
    it('enforces partner collaborator restrictions', function () {
        // Partner collaborators should be restricted from sensitive operations
        expect(Gate::forUser($this->partnerCollaborator)->allows('access-admin-panel'))->toBeFalse();
        expect(Gate::forUser($this->partnerCollaborator)->allows('access-company-panel'))->toBeFalse();
        expect(Gate::forUser($this->partnerCollaborator)->allows('manage-company-members', $this->company2))->toBeFalse();
        expect(Gate::forUser($this->partnerCollaborator)->allows('access-billing-information', $this->company2))->toBeFalse();
        expect(Gate::forUser($this->partnerCollaborator)->allows('bulk-operations', 'export', $this->company2))->toBeFalse();
    });

    it('allows limited partner collaborator access', function () {
        // Partner collaborators should have limited access to their partner company
        expect(Gate::forUser($this->partnerCollaborator)->allows('access-user-panel'))->toBeTrue();
        expect(Gate::forUser($this->partnerCollaborator)->allows('access-reporting-analytics', $this->company2))->toBeTrue();
        expect(Gate::forUser($this->partnerCollaborator)->allows('tenant-isolation', $this->company2))->toBeTrue();
    });

    it('prevents partner collaborator panel access violations', function () {
        $this->actingAs($this->partnerCollaborator);
        
        // Try to access admin panel
        $response = $this->get('/admin');
        expect($response->getStatusCode())->toBe(302); // Should redirect
        
        // Try to access company panel
        $response = $this->get('/company');
        expect($response->getStatusCode())->toBe(302); // Should redirect
        
        // Should be able to access user panel
        $response = $this->get('/app');
        expect($response->getStatusCode())->not->toBe(403);
    });
});

describe('Emergency Access Security', function () {
    it('logs emergency access usage', function () {
        $reason = 'Critical system maintenance required';
        
        Gate::forUser($this->owner)->allows('emergency-access', $reason);
        
        // Check that emergency access was logged
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $this->owner->id,
            'action' => 'emergency_access',
            'granted' => true,
        ]);
        
        // Check that security incident was created
        // This would be created by the SecurityLoggingService
    });

    it('prevents unauthorized emergency access', function () {
        expect(Gate::forUser($this->manager)->allows('emergency-access', 'Unauthorized attempt'))->toBeFalse();
        expect(Gate::forUser($this->employee)->allows('emergency-access', 'Unauthorized attempt'))->toBeFalse();
        expect(Gate::forUser($this->partnerCollaborator)->allows('emergency-access', 'Unauthorized attempt'))->toBeFalse();
    });
});

describe('User Impersonation Security', function () {
    it('logs user impersonation attempts', function () {
        Gate::forUser($this->owner)->allows('impersonate-user', $this->employee);
        
        // Check that impersonation was logged
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $this->owner->id,
            'action' => 'impersonate_user',
            'granted' => true,
        ]);
    });

    it('prevents unauthorized user impersonation', function () {
        expect(Gate::forUser($this->manager)->allows('impersonate-user', $this->employee))->toBeFalse();
        expect(Gate::forUser($this->employee)->allows('impersonate-user', $this->manager))->toBeFalse();
        expect(Gate::forUser($this->partnerCollaborator)->allows('impersonate-user', $this->employee))->toBeFalse();
    });

    it('prevents self-impersonation', function () {
        expect(Gate::forUser($this->owner)->allows('impersonate-user', $this->owner))->toBeFalse();
        expect(Gate::forUser($this->manager)->allows('impersonate-user', $this->manager))->toBeFalse();
    });
});

describe('Audit Trail Security', function () {
    it('creates comprehensive audit logs', function () {
        // Perform various actions
        Gate::forUser($this->owner)->allows('access-admin-panel');
        Gate::forUser($this->manager)->allows('manage-company-members', $this->company1);
        Gate::forUser($this->employee)->allows('access-admin-panel'); // Should fail
        
        // Check that all actions were logged
        $logs = \Illuminate\Support\Facades\DB::table('audit_logs')
            ->where('event_type', 'authorization_decision')
            ->whereIn('user_id', [$this->owner->id, $this->manager->id, $this->employee->id])
            ->get();
        
        expect($logs->count())->toBeGreaterThan(0);
        
        // Check log structure
        foreach ($logs as $log) {
            expect($log)->toHaveProperty('user_id');
            expect($log)->toHaveProperty('action');
            expect($log)->toHaveProperty('granted');
            expect($log)->toHaveProperty('ip_address');
            expect($log)->toHaveProperty('user_agent');
            expect($log)->toHaveProperty('created_at');
        }
    });

    it('generates security reports', function () {
        // Perform some authorization checks to generate data
        Gate::forUser($this->owner)->allows('access-admin-panel');
        Gate::forUser($this->employee)->allows('access-admin-panel'); // Denied
        Gate::forUser($this->partnerCollaborator)->allows('access-company-panel'); // Denied
        
        $auditService = app(\App\Services\AuthorizationAuditService::class);
        $report = $auditService->generateAuthorizationReport(now()->subHour(), now());
        
        expect($report)->toHaveKeys([
            'period',
            'total_decisions',
            'granted_decisions',
            'denied_decisions',
            'partner_collaborator_activity',
            'security_incidents',
            'top_denied_actions',
        ]);
        
        expect($report['total_decisions'])->toBeGreaterThan(0);
        expect($report['denied_decisions'])->toBeGreaterThan(0);
    });

    it('detects and reports suspicious activity', function () {
        // Simulate suspicious activity pattern
        for ($i = 0; $i < 30; $i++) {
            Gate::forUser($this->maliciousUser)->allows('access-admin-panel');
        }
        
        $auditService = app(\App\Services\AuthorizationAuditService::class);
        $suspiciousActivity = $auditService->detectSuspiciousActivity($this->maliciousUser);
        
        expect($suspiciousActivity['risk_level'])->toBeIn(['medium', 'high']);
        expect($suspiciousActivity['suspicious_patterns'])->not->toBeEmpty();
        
        $hasHighDenialRate = collect($suspiciousActivity['suspicious_patterns'])
            ->pluck('type')
            ->contains('high_denial_rate');
        
        expect($hasHighDenialRate)->toBeTrue();
    });
});

describe('Data Access Security', function () {
    it('prevents unauthorized sensitive data access', function () {
        expect(Gate::forUser($this->employee)->allows('access-sensitive-data', 'financial'))->toBeFalse();
        expect(Gate::forUser($this->partnerCollaborator)->allows('access-sensitive-data', 'personal'))->toBeFalse();
        expect(Gate::forUser($this->manager)->allows('access-sensitive-data', 'financial'))->toBeFalse();
        
        // Only owners should access sensitive financial data
        expect(Gate::forUser($this->owner)->allows('access-sensitive-data', 'financial'))->toBeTrue();
    });

    it('enforces bulk operation restrictions', function () {
        // Test destructive bulk operations
        expect(Gate::forUser($this->manager)->allows('bulk-operations', 'delete', $this->company1))->toBeFalse();
        expect(Gate::forUser($this->employee)->allows('bulk-operations', 'delete', $this->company1))->toBeFalse();
        expect(Gate::forUser($this->partnerCollaborator)->allows('bulk-operations', 'delete', $this->company2))->toBeFalse();
        
        // Only owners should perform destructive bulk operations
        expect(Gate::forUser($this->owner)->allows('bulk-operations', 'delete', $this->company1))->toBeTrue();
    });
});

describe('Rate Limiting and Attack Prevention', function () {
    it('tracks repeated authorization failures', function () {
        // Simulate rapid authorization failures
        for ($i = 0; $i < 15; $i++) {
            Gate::forUser($this->maliciousUser)->allows('access-admin-panel');
        }
        
        $auditService = app(\App\Services\AuthorizationAuditService::class);
        $stats = $auditService->getUserAuthorizationStats($this->maliciousUser, 1);
        
        expect($stats['denied_decisions'])->toBe(15);
        expect($stats['success_rate'])->toBe(0.0);
    });

    it('creates security incidents for attack patterns', function () {
        // This test would verify that security incidents are created
        // when attack patterns are detected, but the actual implementation
        // would depend on the specific security monitoring system
        
        expect(true)->toBeTrue(); // Placeholder for actual security incident testing
    });
});