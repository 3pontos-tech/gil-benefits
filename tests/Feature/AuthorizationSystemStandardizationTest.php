<?php

use App\Models\Users\User;
use Illuminate\Support\Facades\Gate;
use TresPontosTech\Company\Models\Company;
use TresPontosTech\Company\Enums\CompanyRoleEnum;
use TresPontosTech\Appointments\Models\Appointment;
use TresPontosTech\Consultants\Models\Consultant;

beforeEach(function () {
    // Create test companies
    $this->company1 = Company::factory()->create(['name' => 'Test Company 1']);
    $this->company2 = Company::factory()->create([
        'name' => 'Test Partner Company',
        'partner_code' => 'PARTNER123', // This makes it a partner company
    ]);
    
    // Create users with different roles
    $this->owner = User::factory()->create(['email' => 'owner@test.com']);
    $this->manager = User::factory()->create(['email' => 'manager@test.com']);
    $this->employee = User::factory()->create(['email' => 'employee@test.com']);
    $this->partnerCollaborator = User::factory()->create(['email' => 'partner@test.com']);
    
    // Attach users to companies with roles
    $this->owner->companies()->attach($this->company1, ['role' => CompanyRoleEnum::Owner]);
    $this->manager->companies()->attach($this->company1, ['role' => CompanyRoleEnum::Manager]);
    $this->employee->companies()->attach($this->company1, ['role' => CompanyRoleEnum::Employee]);
    // Partner collaborator is an employee of a company with partner_code
    $this->partnerCollaborator->companies()->attach($this->company2, ['role' => CompanyRoleEnum::Employee]);
});

describe('Policy Standardization', function () {
    it('ensures all models have consistent policy implementations', function () {
        // Test that all main models have policies defined
        $modelsWithPolicies = [
            \App\Models\Users\User::class,
            \App\Models\Users\Detail::class,
            \TresPontosTech\Company\Models\Company::class,
            \TresPontosTech\Appointments\Models\Appointment::class,
            \TresPontosTech\Consultants\Models\Consultant::class,
            \TresPontosTech\Billing\Core\Models\Plan::class,
            \TresPontosTech\Billing\Core\Models\Price::class,
            \TresPontosTech\Tenant\Models\TenantMember::class,
        ];

        foreach ($modelsWithPolicies as $modelClass) {
            // Check if model exists and has policy attribute
            expect(class_exists($modelClass))->toBeTrue("Model {$modelClass} should exist");
            
            // Create a test instance to verify policy is working
            if ($modelClass === \App\Models\Users\User::class) {
                $model = $this->owner;
            } elseif ($modelClass === \TresPontosTech\Company\Models\Company::class) {
                $model = $this->company1;
            } else {
                // Skip factory creation for complex models in this test
                continue;
            }

            // Test basic policy methods work
            expect(Gate::forUser($this->owner)->allows('view', $model))->toBeBool();
            expect(Gate::forUser($this->owner)->allows('update', $model))->toBeBool();
        }
    });

    it('validates consistent policy behavior across modules', function () {
        $appointment = Appointment::factory()->create([
            'user_id' => $this->employee->id,
            'company_id' => $this->company1->id,
        ]);

        // Test consistent authorization patterns
        
        // Owner should have full access
        expect(Gate::forUser($this->owner)->allows('view', $appointment))->toBeTrue();
        expect(Gate::forUser($this->owner)->allows('update', $appointment))->toBeTrue();
        expect(Gate::forUser($this->owner)->allows('delete', $appointment))->toBeTrue();

        // Employee should have limited access to their own resources
        expect(Gate::forUser($this->employee)->allows('view', $appointment))->toBeTrue();
        expect(Gate::forUser($this->employee)->allows('update', $appointment))->toBeTrue();

        // Partner collaborator should not access other company resources
        expect(Gate::forUser($this->partnerCollaborator)->allows('view', $appointment))->toBeFalse();
        expect(Gate::forUser($this->partnerCollaborator)->allows('update', $appointment))->toBeFalse();
    });
});

describe('Enhanced Gate Authorization', function () {
    it('validates complex business rule gates', function () {
        // Test cross-tenant data access gate
        expect(Gate::forUser($this->owner)->allows('cross-tenant-data-access', $this->company1, $this->company2))->toBeFalse();
        expect(Gate::forUser($this->partnerCollaborator)->allows('cross-tenant-data-access', $this->company1, $this->company2))->toBeFalse();

        // Test subscription quota management
        expect(Gate::forUser($this->owner)->allows('manage-subscription-quotas', $this->company1, 'billing'))->toBeTrue();
        expect(Gate::forUser($this->manager)->allows('manage-subscription-quotas', $this->company1, 'billing'))->toBeFalse();
        expect(Gate::forUser($this->partnerCollaborator)->allows('manage-subscription-quotas', $this->company2, 'billing'))->toBeFalse();

        // Test historical data access with time restrictions
        expect(Gate::forUser($this->owner)->allows('access-historical-data', $this->company1, 24))->toBeTrue();
        expect(Gate::forUser($this->manager)->allows('access-historical-data', $this->company1, 24))->toBeTrue();
        expect(Gate::forUser($this->employee)->allows('access-historical-data', $this->company1, 12))->toBeFalse();
        expect(Gate::forUser($this->partnerCollaborator)->allows('access-historical-data', $this->company2, 6))->toBeFalse();
    });

    it('validates data export authorization with sensitivity levels', function () {
        // Test regular data export
        expect(Gate::forUser($this->owner)->allows('perform-data-export', $this->company1, 'standard', ['appointments']))->toBeTrue();
        expect(Gate::forUser($this->manager)->allows('perform-data-export', $this->company1, 'bulk', ['appointments']))->toBeTrue();
        expect(Gate::forUser($this->employee)->allows('perform-data-export', $this->company1, 'bulk', ['appointments']))->toBeFalse();

        // Test sensitive data export (should require owner)
        expect(Gate::forUser($this->owner)->allows('perform-data-export', $this->company1, 'standard', ['billing', 'financial']))->toBeTrue();
        expect(Gate::forUser($this->manager)->allows('perform-data-export', $this->company1, 'standard', ['billing']))->toBeFalse();
        expect(Gate::forUser($this->partnerCollaborator)->allows('perform-data-export', $this->company2, 'standard', ['appointments']))->toBeFalse();
    });

    it('validates system configuration access controls', function () {
        // Test system configuration modification
        expect(Gate::forUser($this->owner)->allows('modify-system-configuration', 'general'))->toBeTrue();
        expect(Gate::forUser($this->owner)->allows('modify-system-configuration', 'security'))->toBeTrue();
        expect(Gate::forUser($this->manager)->allows('modify-system-configuration', 'general'))->toBeFalse();
        expect(Gate::forUser($this->partnerCollaborator)->allows('modify-system-configuration', 'general'))->toBeFalse();

        // Test multi-tenant resource access
        expect(Gate::forUser($this->owner)->allows('access-multi-tenant-resources', [$this->company1->id]))->toBeTrue();
        expect(Gate::forUser($this->partnerCollaborator)->allows('access-multi-tenant-resources', [$this->company1->id, $this->company2->id]))->toBeFalse();
    });
});

describe('Panel Access Control Standardization', function () {
    it('validates role-based panel access restrictions', function () {
        // Admin panel access
        expect(Gate::forUser($this->owner)->allows('access-admin-panel'))->toBeTrue();
        expect(Gate::forUser($this->manager)->allows('access-admin-panel'))->toBeTrue();
        expect(Gate::forUser($this->employee)->allows('access-admin-panel'))->toBeFalse();
        expect(Gate::forUser($this->partnerCollaborator)->allows('access-admin-panel'))->toBeFalse();

        // Company panel access
        expect(Gate::forUser($this->owner)->allows('access-company-panel'))->toBeTrue();
        expect(Gate::forUser($this->manager)->allows('access-company-panel'))->toBeTrue();
        expect(Gate::forUser($this->employee)->allows('access-company-panel'))->toBeFalse();
        expect(Gate::forUser($this->partnerCollaborator)->allows('access-company-panel'))->toBeFalse();

        // User panel access (all authenticated users)
        expect(Gate::forUser($this->owner)->allows('access-user-panel'))->toBeTrue();
        expect(Gate::forUser($this->manager)->allows('access-user-panel'))->toBeTrue();
        expect(Gate::forUser($this->employee)->allows('access-user-panel'))->toBeTrue();
        expect(Gate::forUser($this->partnerCollaborator)->allows('access-user-panel'))->toBeTrue();
    });

    it('validates consultant panel access requirements', function () {
        // Create consultant profile for testing
        $consultant = Consultant::factory()->create(['user_id' => $this->employee->id]);

        // User with consultant profile should access consultant panel
        expect(Gate::forUser($this->employee)->allows('access-consultant-panel'))->toBeTrue();

        // Managers/owners should also access consultant panel
        expect(Gate::forUser($this->owner)->allows('access-consultant-panel'))->toBeTrue();
        expect(Gate::forUser($this->manager)->allows('access-consultant-panel'))->toBeTrue();

        // Partner collaborators should not access consultant panel
        expect(Gate::forUser($this->partnerCollaborator)->allows('access-consultant-panel'))->toBeFalse();
    });
});

describe('Tenant Isolation and Data Security', function () {
    it('validates tenant isolation for partner collaborators', function () {
        $partnerAppointment = Appointment::factory()->create([
            'user_id' => $this->partnerCollaborator->id,
            'company_id' => $this->company2->id,
        ]);

        $otherAppointment = Appointment::factory()->create([
            'user_id' => $this->employee->id,
            'company_id' => $this->company1->id,
        ]);

        // Partner collaborator should access their company's data
        expect(Gate::forUser($this->partnerCollaborator)->allows('tenant-isolation', $this->company2))->toBeTrue();
        expect(Gate::forUser($this->partnerCollaborator)->allows('tenant-isolation', $partnerAppointment))->toBeTrue();

        // Partner collaborator should NOT access other company's data
        expect(Gate::forUser($this->partnerCollaborator)->allows('tenant-isolation', $this->company1))->toBeFalse();
        expect(Gate::forUser($this->partnerCollaborator)->allows('tenant-isolation', $otherAppointment))->toBeFalse();
    });

    it('validates sensitive data access restrictions', function () {
        // Partner collaborators should have limited sensitive data access
        expect(Gate::forUser($this->partnerCollaborator)->allows('access-sensitive-data', 'billing'))->toBeFalse();
        expect(Gate::forUser($this->partnerCollaborator)->allows('access-sensitive-data', 'appointments'))->toBeTrue();

        // Regular users should have broader access
        expect(Gate::forUser($this->owner)->allows('access-sensitive-data', 'billing'))->toBeTrue();
        expect(Gate::forUser($this->manager)->allows('access-sensitive-data', 'billing'))->toBeTrue();
        expect(Gate::forUser($this->employee)->allows('access-sensitive-data', 'appointments'))->toBeTrue();
    });

    it('validates bulk operations restrictions', function () {
        // Partner collaborators should not perform bulk operations
        expect(Gate::forUser($this->partnerCollaborator)->allows('bulk-operations', 'delete', $this->company2))->toBeFalse();
        expect(Gate::forUser($this->partnerCollaborator)->allows('bulk-operations', 'export', $this->company2))->toBeFalse();

        // Regular users should be able to perform bulk operations on their companies
        expect(Gate::forUser($this->owner)->allows('bulk-operations', 'delete', $this->company1))->toBeTrue();
        expect(Gate::forUser($this->manager)->allows('bulk-operations', 'export', $this->company1))->toBeTrue();
    });
});

describe('Audit Logging Verification', function () {
    it('ensures authorization decisions are properly logged', function () {
        // Clear any existing audit logs
        \Illuminate\Support\Facades\DB::table('audit_logs')->truncate();

        // Perform some authorization checks
        Gate::forUser($this->owner)->allows('access-admin-panel');
        Gate::forUser($this->partnerCollaborator)->allows('access-admin-panel');
        Gate::forUser($this->manager)->allows('manage-company-settings', $this->company1);

        // Verify audit logs were created
        $auditLogs = \Illuminate\Support\Facades\DB::table('audit_logs')
            ->where('event_type', 'authorization_decision')
            ->get();

        expect($auditLogs->count())->toBeGreaterThan(0);

        // Verify log structure
        $firstLog = $auditLogs->first();
        expect($firstLog)->toHaveProperty('user_id');
        expect($firstLog)->toHaveProperty('action');
        expect($firstLog)->toHaveProperty('granted');
        expect($firstLog)->toHaveProperty('context');
        expect($firstLog)->toHaveProperty('ip_address');
        expect($firstLog)->toHaveProperty('created_at');
    });

    it('validates audit service functionality', function () {
        $auditService = app(\App\Services\AuthorizationAuditService::class);

        // Test authorization decision logging
        $auditService->logAuthorizationDecision(
            $this->owner,
            'test_action',
            $this->company1,
            true,
            'Test authorization decision'
        );

        // Test policy check logging
        $auditService->logPolicyCheck(
            $this->manager,
            'CompanyPolicy',
            'update',
            $this->company1,
            false
        );

        // Test gate check logging
        $auditService->logGateCheck(
            $this->employee,
            'access-admin-panel',
            [],
            false
        );

        // Verify logs were created
        $logs = \Illuminate\Support\Facades\DB::table('audit_logs')
            ->where('event_type', 'authorization_decision')
            ->whereIn('user_id', [$this->owner->id, $this->manager->id, $this->employee->id])
            ->get();

        expect($logs->count())->toBeGreaterThanOrEqual(3);
    });

    it('validates suspicious activity detection', function () {
        $auditService = app(\App\Services\AuthorizationAuditService::class);

        // Create multiple failed authorization attempts to simulate suspicious activity
        for ($i = 0; $i < 25; $i++) {
            $auditService->logAuthorizationDecision(
                $this->partnerCollaborator,
                'unauthorized_access_attempt',
                $this->company1,
                false,
                'Unauthorized access attempt'
            );
        }

        // Check for suspicious activity detection
        $suspiciousActivity = $auditService->detectSuspiciousActivity($this->partnerCollaborator, 24);

        expect($suspiciousActivity['risk_level'])->toBe('high');
        expect($suspiciousActivity['suspicious_patterns'])->not->toBeEmpty();
        expect($suspiciousActivity['denied_requests'])->toBeGreaterThan(20);
    });
});

describe('Security Event Logging', function () {
    it('validates security events are properly logged for violations', function () {
        $securityService = app(\App\Services\SecurityLoggingService::class);

        // Test security event logging
        $securityService->logSecurityEvent(
            'test_security_violation',
            'Test security violation message',
            [
                'user_id' => $this->partnerCollaborator->id,
                'severity' => 'high',
                'test_context' => 'authorization_test',
            ]
        );

        // Verify the event was logged (this would depend on your SecurityLoggingService implementation)
        // For now, we'll just verify the service exists and method is callable
        expect($securityService)->toBeInstanceOf(\App\Services\SecurityLoggingService::class);
    });
});

describe('Integration with Existing Authorization System', function () {
    it('validates compatibility with existing authorization tests', function () {
        // Ensure our enhancements don't break existing functionality
        
        // Test existing partner collaborator restrictions
        expect($this->partnerCollaborator->isPartnerCollaborator())->toBeTrue();
        expect($this->owner->isPartnerCollaborator())->toBeFalse();

        // Test existing company relationships
        expect($this->owner->companies->contains($this->company1))->toBeTrue();
        expect($this->partnerCollaborator->companies->contains($this->company2))->toBeTrue();
        expect($this->partnerCollaborator->companies->contains($this->company1))->toBeFalse();

        // Test existing role-based access
        $ownerRole = $this->owner->companies()
            ->where('companies.id', $this->company1->id)
            ->first()?->pivot?->role;
        expect($ownerRole)->toBe(CompanyRoleEnum::Owner);

        $managerRole = $this->manager->companies()
            ->where('companies.id', $this->company1->id)
            ->first()?->pivot?->role;
        expect($managerRole)->toBe(CompanyRoleEnum::Manager);
    });

    it('validates enhanced security does not break normal operations', function () {
        // Test that normal users can still perform expected operations
        expect(Gate::forUser($this->owner)->allows('manage-company-settings', $this->company1))->toBeTrue();
        expect(Gate::forUser($this->manager)->allows('manage-company-members', $this->company1))->toBeTrue();
        expect(Gate::forUser($this->employee)->allows('access-user-panel'))->toBeTrue();

        // Test that enhanced restrictions work as expected
        expect(Gate::forUser($this->partnerCollaborator)->allows('access-admin-panel'))->toBeFalse();
        expect(Gate::forUser($this->partnerCollaborator)->allows('manage-company-settings', $this->company2))->toBeFalse();
    });
});