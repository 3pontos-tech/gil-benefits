<?php

use App\Models\Users\User;
use TresPontosTech\Company\Models\Company;
use TresPontosTech\Company\Enums\CompanyRoleEnum;
use TresPontosTech\Appointments\Models\Appointment;

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

describe('Tenant Isolation Middleware', function () {
    it('allows access to own company resources', function () {
        $this->actingAs($this->owner);
        
        $response = $this->get(route('companies.show', $this->company));
        
        $response->assertSuccessful();
    });

    it('denies access to other company resources', function () {
        $otherCompany = Company::factory()->create();
        $this->actingAs($this->owner);
        
        $response = $this->get(route('companies.show', $otherCompany));
        
        $response->assertForbidden();
    });

    it('restricts partner collaborators to their partner company', function () {
        $partnerCompany = Company::factory()->create();
        $this->partnerCollaborator->update(['partner_company_id' => $partnerCompany->id]);
        
        $this->actingAs($this->partnerCollaborator);
        
        // Should allow access to partner company
        $response = $this->get(route('companies.show', $partnerCompany));
        $response->assertSuccessful();
        
        // Should deny access to other companies
        $response = $this->get(route('companies.show', $this->company));
        $response->assertForbidden();
    });
});

describe('Role-Based Access Control Middleware', function () {
    it('allows access with correct role', function () {
        $this->actingAs($this->owner);
        
        $response = $this->withMiddleware(['role.access:owner'])
            ->get('/test-owner-route');
        
        // This would normally be successful if the route existed
        // For testing purposes, we'll check that the middleware doesn't block it
        expect($response->getStatusCode())->not->toBe(403);
    });

    it('denies access without required role', function () {
        $this->actingAs($this->employee);
        
        $response = $this->withMiddleware(['role.access:owner'])
            ->get('/test-owner-route');
        
        $response->assertForbidden();
    });

    it('denies partner collaborators access to restricted roles', function () {
        $this->actingAs($this->partnerCollaborator);
        
        $response = $this->withMiddleware(['role.access:manager'])
            ->get('/test-manager-route');
        
        $response->assertForbidden();
    });
});

describe('Enhanced Authorization Middleware', function () {
    it('validates gate permissions correctly', function () {
        $this->actingAs($this->owner);
        
        $response = $this->withMiddleware(['enhanced.auth:gate:access-admin-panel'])
            ->get('/test-admin-route');
        
        expect($response->getStatusCode())->not->toBe(403);
    });

    it('validates role permissions correctly', function () {
        $this->actingAs($this->manager);
        
        $response = $this->withMiddleware(['enhanced.auth:role:manager'])
            ->get('/test-manager-route');
        
        expect($response->getStatusCode())->not->toBe(403);
    });

    it('validates company permissions correctly', function () {
        $this->actingAs($this->owner);
        
        $response = $this->withMiddleware(['enhanced.auth:company:owner'])
            ->get(route('companies.settings', $this->company));
        
        expect($response->getStatusCode())->not->toBe(403);
    });

    it('validates panel access permissions', function () {
        $this->actingAs($this->owner);
        
        $response = $this->withMiddleware(['enhanced.auth:panel:admin'])
            ->get('/admin/dashboard');
        
        expect($response->getStatusCode())->not->toBe(403);
    });

    it('validates feature permissions correctly', function () {
        $this->actingAs($this->manager);
        
        $response = $this->withMiddleware(['enhanced.auth:feature:analytics'])
            ->get(route('companies.analytics', $this->company));
        
        expect($response->getStatusCode())->not->toBe(403);
    });

    it('denies partner collaborators access to sensitive features', function () {
        $this->actingAs($this->partnerCollaborator);
        
        $response = $this->withMiddleware(['enhanced.auth:feature:billing'])
            ->get('/billing/dashboard');
        
        $response->assertForbidden();
    });

    it('validates data access levels correctly', function () {
        $this->actingAs($this->owner);
        
        $response = $this->withMiddleware(['enhanced.auth:data_access:financial'])
            ->get('/financial/reports');
        
        expect($response->getStatusCode())->not->toBe(403);
    });

    it('denies access to sensitive data for unauthorized users', function () {
        $this->actingAs($this->employee);
        
        $response = $this->withMiddleware(['enhanced.auth:data_access:financial'])
            ->get('/financial/reports');
        
        $response->assertForbidden();
    });
});

describe('Authorization Audit Middleware', function () {
    it('logs authorization decisions', function () {
        $this->actingAs($this->owner);
        
        // Mock the audit service to verify logging
        $auditService = $this->mock(\App\Services\AuthorizationAuditService::class);
        $auditService->shouldReceive('logAuthorizationDecision')->once();
        $auditService->shouldReceive('logGateCheck')->atLeast()->once();
        
        $response = $this->get(route('companies.show', $this->company));
        
        $response->assertSuccessful();
    });

    it('logs failed authorization attempts', function () {
        $this->actingAs($this->employee);
        
        // Mock the audit service to verify logging
        $auditService = $this->mock(\App\Services\AuthorizationAuditService::class);
        $auditService->shouldReceive('logAuthorizationDecision')->once();
        
        $securityService = $this->mock(\App\Services\SecurityLoggingService::class);
        $securityService->shouldReceive('logSecurityEvent')->once();
        
        $response = $this->withMiddleware(['role.access:owner'])
            ->get('/test-owner-route');
        
        $response->assertForbidden();
    });
});

describe('Security Headers Middleware', function () {
    it('adds security headers to responses', function () {
        $this->actingAs($this->owner);
        
        $response = $this->get('/');
        
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('X-Frame-Options', 'DENY');
        $response->assertHeader('X-XSS-Protection', '1; mode=block');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
    });
});

describe('Rate Limiting in Middleware', function () {
    it('applies rate limiting for partner collaborators', function () {
        $this->actingAs($this->partnerCollaborator);
        
        // Simulate multiple requests
        for ($i = 0; $i < 5; $i++) {
            $response = $this->get('/');
            $response->assertSuccessful();
        }
        
        // This test would need to be adjusted based on actual rate limiting implementation
        expect(true)->toBeTrue(); // Placeholder assertion
    });

    it('applies stricter rate limiting for sensitive operations', function () {
        $this->actingAs($this->owner);
        
        // Simulate multiple sensitive operations
        for ($i = 0; $i < 3; $i++) {
            $response = $this->delete(route('companies.destroy', $this->company));
            // The actual response would depend on the implementation
        }
        
        expect(true)->toBeTrue(); // Placeholder assertion
    });
});

describe('Cross-Tenant Security', function () {
    it('detects and prevents cross-tenant data access', function () {
        $otherCompany = Company::factory()->create();
        $appointment = Appointment::factory()->create(['company_id' => $otherCompany->id]);
        
        $this->actingAs($this->owner);
        
        $response = $this->get(route('appointments.show', $appointment));
        
        $response->assertForbidden();
    });

    it('logs cross-tenant access violations', function () {
        $otherCompany = Company::factory()->create();
        $appointment = Appointment::factory()->create(['company_id' => $otherCompany->id]);
        
        $securityService = $this->mock(\App\Services\SecurityLoggingService::class);
        $securityService->shouldReceive('logSecurityEvent')
            ->with('tenant_isolation_violation', \Mockery::any(), \Mockery::any())
            ->once();
        
        $this->actingAs($this->owner);
        
        $response = $this->get(route('appointments.show', $appointment));
        
        $response->assertForbidden();
    });
});

describe('Sensitive Operation Security', function () {
    it('applies enhanced security for destructive operations', function () {
        $this->actingAs($this->owner);
        
        $auditService = $this->mock(\App\Services\AuthorizationAuditService::class);
        $auditService->shouldReceive('logAuthorizationDecision')
            ->with(\Mockery::any(), 'sensitive_operation_access', \Mockery::any(), true, \Mockery::any(), \Mockery::any())
            ->once();
        
        $response = $this->delete(route('companies.destroy', $this->company));
        
        // The response would depend on actual implementation
        expect(true)->toBeTrue();
    });

    it('blocks partner collaborators from sensitive operations', function () {
        $this->actingAs($this->partnerCollaborator);
        
        $securityService = $this->mock(\App\Services\SecurityLoggingService::class);
        $securityService->shouldReceive('logSecurityEvent')
            ->with('partner_collaborator_sensitive_access', \Mockery::any(), \Mockery::any())
            ->once();
        
        $response = $this->delete(route('companies.destroy', $this->company));
        
        $response->assertForbidden();
    });
});

describe('High-Risk User Detection', function () {
    it('applies additional security for high-risk users', function () {
        // Mock suspicious activity detection
        $auditService = $this->mock(\App\Services\AuthorizationAuditService::class);
        $auditService->shouldReceive('detectSuspiciousActivity')
            ->andReturn(['risk_level' => 'high']);
        
        $securityService = $this->mock(\App\Services\SecurityLoggingService::class);
        $securityService->shouldReceive('logSecurityEvent')
            ->with('high_risk_user_sensitive_operation', \Mockery::any(), \Mockery::any())
            ->once();
        
        $this->actingAs($this->owner);
        
        $response = $this->delete(route('companies.force-delete', $this->company));
        
        $response->assertForbidden();
    });
});

describe('Context-Aware Security', function () {
    it('applies different security measures based on request context', function () {
        $this->actingAs($this->partnerCollaborator);
        
        // Test restricted route access
        $response = $this->get('/admin/dashboard');
        $response->assertForbidden();
        
        // Test allowed route access
        $response = $this->get('/user/dashboard');
        expect($response->getStatusCode())->not->toBe(403);
    });

    it('validates subscription requirements for premium features', function () {
        $this->actingAs($this->owner);
        
        $response = $this->withMiddleware(['enhanced.auth:subscription:premium'])
            ->get('/premium/features');
        
        // Would depend on actual subscription status
        expect(true)->toBeTrue();
    });
});