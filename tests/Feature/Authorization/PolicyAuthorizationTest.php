<?php

use App\Models\Users\User;
use TresPontosTech\Company\Models\Company;
use TresPontosTech\Company\Enums\CompanyRoleEnum;
use TresPontosTech\Appointments\Models\Appointment;
use TresPontosTech\Consultants\Models\Consultant;
use TresPontosTech\Billing\Core\Models\Subscriptions\Subscription;
use TresPontosTech\Billing\Core\Models\Subscriptions\SubscriptionItem;

beforeEach(function () {
    $this->company = Company::factory()->create();
    $this->otherCompany = Company::factory()->create();
    
    $this->owner = User::factory()->create();
    $this->manager = User::factory()->create();
    $this->employee = User::factory()->create();
    $this->partnerCollaborator = User::factory()->partnerCollaborator()->create();
    $this->outsideUser = User::factory()->create();
    
    // Attach users to company with roles
    $this->company->users()->attach($this->owner, ['role' => CompanyRoleEnum::Owner]);
    $this->company->users()->attach($this->manager, ['role' => CompanyRoleEnum::Manager]);
    $this->company->users()->attach($this->employee, ['role' => CompanyRoleEnum::Employee]);
    
    // Set up partner collaborator
    $this->partnerCollaborator->update(['partner_company_id' => $this->company->id]);
});

describe('User Policy Authorization', function () {
    it('allows users to view their own profile', function () {
        expect($this->owner->can('view', $this->owner))->toBeTrue();
        expect($this->manager->can('view', $this->manager))->toBeTrue();
        expect($this->employee->can('view', $this->employee))->toBeTrue();
    });

    it('allows owners and managers to view users in their company', function () {
        expect($this->owner->can('view', $this->employee))->toBeTrue();
        expect($this->manager->can('view', $this->employee))->toBeTrue();
        expect($this->employee->can('view', $this->owner))->toBeFalse();
    });

    it('denies access to users from other companies', function () {
        expect($this->owner->can('view', $this->outsideUser))->toBeFalse();
        expect($this->manager->can('view', $this->outsideUser))->toBeFalse();
        expect($this->employee->can('view', $this->outsideUser))->toBeFalse();
    });

    it('allows owners and managers to create users', function () {
        expect($this->owner->can('create', User::class))->toBeTrue();
        expect($this->manager->can('create', User::class))->toBeTrue();
        expect($this->employee->can('create', User::class))->toBeFalse();
    });

    it('allows users to update their own profile', function () {
        expect($this->owner->can('update', $this->owner))->toBeTrue();
        expect($this->manager->can('update', $this->manager))->toBeTrue();
        expect($this->employee->can('update', $this->employee))->toBeTrue();
    });

    it('allows only owners to update other users', function () {
        expect($this->owner->can('update', $this->employee))->toBeTrue();
        expect($this->manager->can('update', $this->employee))->toBeFalse();
        expect($this->employee->can('update', $this->manager))->toBeFalse();
    });

    it('prevents users from deleting themselves', function () {
        expect($this->owner->can('delete', $this->owner))->toBeFalse();
        expect($this->manager->can('delete', $this->manager))->toBeFalse();
        expect($this->employee->can('delete', $this->employee))->toBeFalse();
    });

    it('allows only owners to delete other users', function () {
        expect($this->owner->can('delete', $this->employee))->toBeTrue();
        expect($this->manager->can('delete', $this->employee))->toBeFalse();
        expect($this->employee->can('delete', $this->manager))->toBeFalse();
    });
});

describe('Company Policy Authorization', function () {
    it('allows users to view companies they belong to', function () {
        expect($this->owner->can('view', $this->company))->toBeTrue();
        expect($this->manager->can('view', $this->company))->toBeTrue();
        expect($this->employee->can('view', $this->company))->toBeTrue();
    });

    it('denies access to companies users do not belong to', function () {
        expect($this->owner->can('view', $this->otherCompany))->toBeFalse();
        expect($this->manager->can('view', $this->otherCompany))->toBeFalse();
        expect($this->employee->can('view', $this->otherCompany))->toBeFalse();
    });

    it('restricts partner collaborators to their partner company', function () {
        expect($this->partnerCollaborator->can('view', $this->company))->toBeTrue();
        expect($this->partnerCollaborator->can('view', $this->otherCompany))->toBeFalse();
    });

    it('allows users without companies to create new companies', function () {
        $newUser = User::factory()->create();
        expect($newUser->can('create', Company::class))->toBeTrue();
    });

    it('denies partner collaborators from creating companies', function () {
        expect($this->partnerCollaborator->can('create', Company::class))->toBeFalse();
    });

    it('allows only owners to update company details', function () {
        expect($this->owner->can('update', $this->company))->toBeTrue();
        expect($this->manager->can('update', $this->company))->toBeFalse();
        expect($this->employee->can('update', $this->company))->toBeFalse();
        expect($this->partnerCollaborator->can('update', $this->company))->toBeFalse();
    });

    it('allows only owners to delete companies', function () {
        expect($this->owner->can('delete', $this->company))->toBeTrue();
        expect($this->manager->can('delete', $this->company))->toBeFalse();
        expect($this->employee->can('delete', $this->company))->toBeFalse();
        expect($this->partnerCollaborator->can('delete', $this->company))->toBeFalse();
    });

    it('allows owners and managers to manage company members', function () {
        expect($this->owner->can('manageMembers', $this->company))->toBeTrue();
        expect($this->manager->can('manageMembers', $this->company))->toBeTrue();
        expect($this->employee->can('manageMembers', $this->company))->toBeFalse();
        expect($this->partnerCollaborator->can('manageMembers', $this->company))->toBeFalse();
    });

    it('allows only owners to manage company settings', function () {
        expect($this->owner->can('manageSettings', $this->company))->toBeTrue();
        expect($this->manager->can('manageSettings', $this->company))->toBeFalse();
        expect($this->employee->can('manageSettings', $this->company))->toBeFalse();
        expect($this->partnerCollaborator->can('manageSettings', $this->company))->toBeFalse();
    });

    it('allows owners and managers to view analytics', function () {
        expect($this->owner->can('viewAnalytics', $this->company))->toBeTrue();
        expect($this->manager->can('viewAnalytics', $this->company))->toBeTrue();
        expect($this->employee->can('viewAnalytics', $this->company))->toBeFalse();
        expect($this->partnerCollaborator->can('viewAnalytics', $this->company))->toBeFalse();
    });

    it('allows only owners to access billing information', function () {
        expect($this->owner->can('accessBilling', $this->company))->toBeTrue();
        expect($this->manager->can('accessBilling', $this->company))->toBeFalse();
        expect($this->employee->can('accessBilling', $this->company))->toBeFalse();
        expect($this->partnerCollaborator->can('accessBilling', $this->company))->toBeFalse();
    });
});

describe('Appointment Policy Authorization', function () {
    beforeEach(function () {
        $this->appointment = Appointment::factory()->create([
            'user_id' => $this->employee->id,
            'company_id' => $this->company->id,
        ]);
        
        $this->otherAppointment = Appointment::factory()->create([
            'user_id' => $this->outsideUser->id,
            'company_id' => $this->otherCompany->id,
        ]);
    });

    it('allows all authenticated users to view appointments list', function () {
        expect($this->owner->can('viewAny', Appointment::class))->toBeTrue();
        expect($this->manager->can('viewAny', Appointment::class))->toBeTrue();
        expect($this->employee->can('viewAny', Appointment::class))->toBeTrue();
        expect($this->partnerCollaborator->can('viewAny', Appointment::class))->toBeTrue();
    });

    it('allows users to view their own appointments', function () {
        expect($this->employee->can('view', $this->appointment))->toBeTrue();
    });

    it('allows owners and managers to view appointments in their company', function () {
        expect($this->owner->can('view', $this->appointment))->toBeTrue();
        expect($this->manager->can('view', $this->appointment))->toBeTrue();
    });

    it('denies access to appointments from other companies', function () {
        expect($this->owner->can('view', $this->otherAppointment))->toBeFalse();
        expect($this->manager->can('view', $this->otherAppointment))->toBeFalse();
        expect($this->employee->can('view', $this->otherAppointment))->toBeFalse();
    });

    it('allows all authenticated users to create appointments', function () {
        expect($this->owner->can('create', Appointment::class))->toBeTrue();
        expect($this->manager->can('create', Appointment::class))->toBeTrue();
        expect($this->employee->can('create', Appointment::class))->toBeTrue();
        expect($this->partnerCollaborator->can('create', Appointment::class))->toBeTrue();
    });

    it('allows users to update their own appointments if not completed', function () {
        $this->appointment->update(['status' => 'pending']);
        expect($this->employee->can('update', $this->appointment))->toBeTrue();
        
        $this->appointment->update(['status' => 'completed']);
        expect($this->employee->can('update', $this->appointment))->toBeFalse();
    });

    it('allows owners and managers to update appointments in their company', function () {
        expect($this->owner->can('update', $this->appointment))->toBeTrue();
        expect($this->manager->can('update', $this->appointment))->toBeTrue();
    });

    it('allows users to cancel their own appointments if not completed', function () {
        $this->appointment->update(['status' => 'pending']);
        expect($this->employee->can('delete', $this->appointment))->toBeTrue();
        
        $this->appointment->update(['status' => 'completed']);
        expect($this->employee->can('delete', $this->appointment))->toBeFalse();
    });

    it('allows only owners to delete other users appointments', function () {
        expect($this->owner->can('delete', $this->appointment))->toBeTrue();
        expect($this->manager->can('delete', $this->appointment))->toBeFalse();
    });
});

describe('Consultant Policy Authorization', function () {
    beforeEach(function () {
        $this->consultant = Consultant::factory()->create([
            'user_id' => $this->employee->id,
        ]);
        
        $this->otherConsultant = Consultant::factory()->create([
            'user_id' => $this->outsideUser->id,
        ]);
    });

    it('allows all authenticated users to view consultants', function () {
        expect($this->owner->can('viewAny', Consultant::class))->toBeTrue();
        expect($this->manager->can('viewAny', Consultant::class))->toBeTrue();
        expect($this->employee->can('viewAny', Consultant::class))->toBeTrue();
        expect($this->partnerCollaborator->can('viewAny', Consultant::class))->toBeTrue();
    });

    it('allows all authenticated users to view consultant profiles', function () {
        expect($this->owner->can('view', $this->consultant))->toBeTrue();
        expect($this->manager->can('view', $this->consultant))->toBeTrue();
        expect($this->employee->can('view', $this->consultant))->toBeTrue();
        expect($this->partnerCollaborator->can('view', $this->consultant))->toBeTrue();
    });

    it('allows only owners and managers to create consultants', function () {
        expect($this->owner->can('create', Consultant::class))->toBeTrue();
        expect($this->manager->can('create', Consultant::class))->toBeTrue();
        expect($this->employee->can('create', Consultant::class))->toBeFalse();
        expect($this->partnerCollaborator->can('create', Consultant::class))->toBeFalse();
    });

    it('allows consultants to update their own profile', function () {
        expect($this->employee->can('update', $this->consultant))->toBeTrue();
    });

    it('allows owners and managers to update consultants in their company', function () {
        expect($this->owner->can('update', $this->consultant))->toBeTrue();
        expect($this->manager->can('update', $this->consultant))->toBeTrue();
    });

    it('denies access to update consultants from other companies', function () {
        expect($this->owner->can('update', $this->otherConsultant))->toBeFalse();
        expect($this->manager->can('update', $this->otherConsultant))->toBeFalse();
    });

    it('allows only owners to delete consultants in their company', function () {
        expect($this->owner->can('delete', $this->consultant))->toBeTrue();
        expect($this->manager->can('delete', $this->consultant))->toBeFalse();
        expect($this->employee->can('delete', $this->consultant))->toBeFalse();
    });
});

describe('Subscription Policy Authorization', function () {
    beforeEach(function () {
        $this->subscription = Subscription::factory()->create([
            'user_id' => $this->owner->id,
        ]);
        
        $this->otherSubscription = Subscription::factory()->create([
            'user_id' => $this->outsideUser->id,
        ]);
    });

    it('denies partner collaborators from viewing subscriptions', function () {
        expect($this->partnerCollaborator->can('viewAny', Subscription::class))->toBeFalse();
        expect($this->partnerCollaborator->can('view', $this->subscription))->toBeFalse();
    });

    it('allows owners and managers to view subscriptions', function () {
        expect($this->owner->can('viewAny', Subscription::class))->toBeTrue();
        expect($this->manager->can('viewAny', Subscription::class))->toBeTrue();
        expect($this->employee->can('viewAny', Subscription::class))->toBeFalse();
    });

    it('allows users to view their own subscriptions', function () {
        expect($this->owner->can('view', $this->subscription))->toBeTrue();
    });

    it('denies access to other users subscriptions', function () {
        expect($this->owner->can('view', $this->otherSubscription))->toBeFalse();
        expect($this->manager->can('view', $this->otherSubscription))->toBeFalse();
    });

    it('allows only owners to create subscriptions', function () {
        expect($this->owner->can('create', Subscription::class))->toBeTrue();
        expect($this->manager->can('create', Subscription::class))->toBeFalse();
        expect($this->employee->can('create', Subscription::class))->toBeFalse();
        expect($this->partnerCollaborator->can('create', Subscription::class))->toBeFalse();
    });

    it('allows only owners to update subscriptions', function () {
        expect($this->owner->can('update', $this->subscription))->toBeTrue();
        expect($this->manager->can('update', $this->subscription))->toBeFalse();
        expect($this->employee->can('update', $this->subscription))->toBeFalse();
        expect($this->partnerCollaborator->can('update', $this->subscription))->toBeFalse();
    });

    it('allows only owners to manage subscription billing', function () {
        expect($this->owner->can('manageBilling', $this->subscription))->toBeTrue();
        expect($this->manager->can('manageBilling', $this->subscription))->toBeFalse();
        expect($this->partnerCollaborator->can('manageBilling', $this->subscription))->toBeFalse();
    });

    it('allows owners and managers to view subscription invoices', function () {
        expect($this->owner->can('viewInvoices', $this->subscription))->toBeTrue();
        expect($this->manager->can('viewInvoices', $this->subscription))->toBeTrue();
        expect($this->employee->can('viewInvoices', $this->subscription))->toBeFalse();
        expect($this->partnerCollaborator->can('viewInvoices', $this->subscription))->toBeFalse();
    });
});

describe('SubscriptionItem Policy Authorization', function () {
    beforeEach(function () {
        $this->subscription = Subscription::factory()->create([
            'user_id' => $this->owner->id,
        ]);
        
        $this->subscriptionItem = SubscriptionItem::factory()->create([
            'subscription_id' => $this->subscription->id,
        ]);
        
        $this->otherSubscription = Subscription::factory()->create([
            'user_id' => $this->outsideUser->id,
        ]);
        
        $this->otherSubscriptionItem = SubscriptionItem::factory()->create([
            'subscription_id' => $this->otherSubscription->id,
        ]);
    });

    it('denies partner collaborators from viewing subscription items', function () {
        expect($this->partnerCollaborator->can('viewAny', SubscriptionItem::class))->toBeFalse();
        expect($this->partnerCollaborator->can('view', $this->subscriptionItem))->toBeFalse();
    });

    it('allows owners and managers to view subscription items', function () {
        expect($this->owner->can('viewAny', SubscriptionItem::class))->toBeTrue();
        expect($this->manager->can('viewAny', SubscriptionItem::class))->toBeTrue();
        expect($this->employee->can('viewAny', SubscriptionItem::class))->toBeFalse();
    });

    it('allows users to view their own subscription items', function () {
        expect($this->owner->can('view', $this->subscriptionItem))->toBeTrue();
    });

    it('denies access to other users subscription items', function () {
        expect($this->owner->can('view', $this->otherSubscriptionItem))->toBeFalse();
        expect($this->manager->can('view', $this->otherSubscriptionItem))->toBeFalse();
    });

    it('allows only owners to create subscription items', function () {
        expect($this->owner->can('create', SubscriptionItem::class))->toBeTrue();
        expect($this->manager->can('create', SubscriptionItem::class))->toBeFalse();
        expect($this->employee->can('create', SubscriptionItem::class))->toBeFalse();
        expect($this->partnerCollaborator->can('create', SubscriptionItem::class))->toBeFalse();
    });

    it('allows only owners to update subscription items', function () {
        expect($this->owner->can('update', $this->subscriptionItem))->toBeTrue();
        expect($this->manager->can('update', $this->subscriptionItem))->toBeFalse();
        expect($this->employee->can('update', $this->subscriptionItem))->toBeFalse();
        expect($this->partnerCollaborator->can('update', $this->subscriptionItem))->toBeFalse();
    });

    it('allows only owners to manage subscription item quantities', function () {
        expect($this->owner->can('manageQuantity', $this->subscriptionItem))->toBeTrue();
        expect($this->manager->can('manageQuantity', $this->subscriptionItem))->toBeFalse();
        expect($this->partnerCollaborator->can('manageQuantity', $this->subscriptionItem))->toBeFalse();
    });

    it('allows owners and managers to view subscription item usage', function () {
        expect($this->owner->can('viewUsage', $this->subscriptionItem))->toBeTrue();
        expect($this->manager->can('viewUsage', $this->subscriptionItem))->toBeTrue();
        expect($this->employee->can('viewUsage', $this->subscriptionItem))->toBeFalse();
        expect($this->partnerCollaborator->can('viewUsage', $this->subscriptionItem))->toBeFalse();
    });
});

describe('Tenant Isolation in Policies', function () {
    it('enforces tenant isolation through base policy', function () {
        $otherCompanyAppointment = Appointment::factory()->create([
            'user_id' => $this->outsideUser->id,
            'company_id' => $this->otherCompany->id,
        ]);
        
        // Even owners cannot access resources from other companies
        expect($this->owner->can('view', $otherCompanyAppointment))->toBeFalse();
        expect($this->owner->can('update', $otherCompanyAppointment))->toBeFalse();
        expect($this->owner->can('delete', $otherCompanyAppointment))->toBeFalse();
    });

    it('logs tenant isolation checks in policies', function () {
        $auditService = $this->mock(\App\Services\AuthorizationAuditService::class);
        $auditService->shouldReceive('logPolicyCheck')->atLeast()->once();
        $auditService->shouldReceive('logAuthorizationDecision')->atLeast()->once();
        
        $appointment = Appointment::factory()->create([
            'user_id' => $this->employee->id,
            'company_id' => $this->company->id,
        ]);
        
        $result = $this->owner->can('view', $appointment);
        
        expect($result)->toBeTrue();
    });

    it('restricts partner collaborators to their partner company data only', function () {
        $partnerCompany = Company::factory()->create();
        $this->partnerCollaborator->update(['partner_company_id' => $partnerCompany->id]);
        
        $partnerAppointment = Appointment::factory()->create([
            'company_id' => $partnerCompany->id,
        ]);
        
        $otherAppointment = Appointment::factory()->create([
            'company_id' => $this->company->id,
        ]);
        
        expect($this->partnerCollaborator->can('view', $partnerAppointment))->toBeTrue();
        expect($this->partnerCollaborator->can('view', $otherAppointment))->toBeFalse();
    });
});