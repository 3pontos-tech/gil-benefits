<?php

use App\Models\Users\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use TresPontosTech\Company\Models\Company;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Create test companies
    $this->company1 = Company::factory()->create(['name' => 'Company 1']);
    $this->company2 = Company::factory()->create(['name' => 'Company 2', 'partner_code' => 'PARTNER123']);
    
    // Create test users with different roles
    $this->owner = User::factory()->create(['email' => 'owner@test.com']);
    $this->manager = User::factory()->create(['email' => 'manager@test.com']);
    $this->employee = User::factory()->create(['email' => 'employee@test.com']);
    $this->partnerCollaborator = User::factory()->create(['email' => 'partner@test.com']);
    
    // Attach users to companies with roles
    $this->owner->companies()->attach($this->company1, ['role' => \TresPontosTech\Company\Enums\CompanyRoleEnum::Owner->value]);
    $this->manager->companies()->attach($this->company1, ['role' => \TresPontosTech\Company\Enums\CompanyRoleEnum::Manager->value]);
    $this->employee->companies()->attach($this->company1, ['role' => \TresPontosTech\Company\Enums\CompanyRoleEnum::Employee->value]);
    $this->partnerCollaborator->companies()->attach($this->company2, ['role' => \TresPontosTech\Company\Enums\CompanyRoleEnum::Employee->value]);
});

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

it('allows owners to access company panel', function () {
    expect(Gate::forUser($this->owner)->allows('access-company-panel'))->toBeTrue();
});

it('allows managers to access company panel', function () {
    expect(Gate::forUser($this->manager)->allows('access-company-panel'))->toBeTrue();
});

it('denies employees access to company panel', function () {
    expect(Gate::forUser($this->employee)->allows('access-company-panel'))->toBeFalse();
});

it('denies partner collaborators access to company panel', function () {
    expect(Gate::forUser($this->partnerCollaborator)->allows('access-company-panel'))->toBeFalse();
});

it('allows all users to access user panel', function () {
    expect(Gate::forUser($this->owner)->allows('access-user-panel'))->toBeTrue();
    expect(Gate::forUser($this->manager)->allows('access-user-panel'))->toBeTrue();
    expect(Gate::forUser($this->employee)->allows('access-user-panel'))->toBeTrue();
    expect(Gate::forUser($this->partnerCollaborator)->allows('access-user-panel'))->toBeTrue();
});

it('enforces tenant isolation for partner collaborators', function () {
    // Partner collaborator should only access their partner company
    expect(Gate::forUser($this->partnerCollaborator)->allows('tenant-isolation', $this->company2))->toBeTrue();
    expect(Gate::forUser($this->partnerCollaborator)->allows('tenant-isolation', $this->company1))->toBeFalse();
});

it('allows non-partner users to access their companies', function () {
    expect(Gate::forUser($this->owner)->allows('tenant-isolation', $this->company1))->toBeTrue();
    expect(Gate::forUser($this->manager)->allows('tenant-isolation', $this->company1))->toBeTrue();
    expect(Gate::forUser($this->employee)->allows('tenant-isolation', $this->company1))->toBeTrue();
});

it('allows owners to manage company settings', function () {
    expect(Gate::forUser($this->owner)->allows('manage-company-settings', $this->company1))->toBeTrue();
});

it('denies managers from managing company settings', function () {
    expect(Gate::forUser($this->manager)->allows('manage-company-settings', $this->company1))->toBeFalse();
});

it('allows owners and managers to manage company members', function () {
    expect(Gate::forUser($this->owner)->allows('manage-company-members', $this->company1))->toBeTrue();
    expect(Gate::forUser($this->manager)->allows('manage-company-members', $this->company1))->toBeTrue();
});

it('denies employees from managing company members', function () {
    expect(Gate::forUser($this->employee)->allows('manage-company-members', $this->company1))->toBeFalse();
});

it('allows owners and managers to view company analytics', function () {
    expect(Gate::forUser($this->owner)->allows('view-company-analytics', $this->company1))->toBeTrue();
    expect(Gate::forUser($this->manager)->allows('view-company-analytics', $this->company1))->toBeTrue();
});

it('denies employees from viewing company analytics', function () {
    expect(Gate::forUser($this->employee)->allows('view-company-analytics', $this->company1))->toBeFalse();
});

it('allows owners to access billing information', function () {
    expect(Gate::forUser($this->owner)->allows('access-billing-information', $this->company1))->toBeTrue();
});

it('denies managers from accessing billing information', function () {
    expect(Gate::forUser($this->manager)->allows('access-billing-information', $this->company1))->toBeFalse();
});

it('correctly identifies partner collaborator restrictions', function () {
    expect(Gate::forUser($this->partnerCollaborator)->allows('partner-collaborator-restrictions'))->toBeTrue();
    expect(Gate::forUser($this->owner)->allows('partner-collaborator-restrictions'))->toBeFalse();
});

it('logs authorization decisions to audit trail', function () {
    // Test that authorization decisions are logged
    $this->actingAs($this->owner);
    
    Gate::allows('access-admin-panel');
    
    // Check that audit log was created
    $this->assertDatabaseHas('audit_logs', [
        'event_type' => 'authorization_decision',
        'user_id' => $this->owner->id,
        'granted' => true,
    ]);
});

it('logs authorization failures with context', function () {
    $this->actingAs($this->employee);
    
    Gate::denies('access-admin-panel');
    
    // Check that audit log was created for denial
    $this->assertDatabaseHas('audit_logs', [
        'event_type' => 'authorization_decision',
        'user_id' => $this->employee->id,
        'granted' => false,
    ]);
});

it('enforces policy-based authorization for users', function () {
    // Test user policy authorization
    $otherUser = User::factory()->create();
    
    // Users can view their own profile
    expect($this->owner->can('view', $this->owner))->toBeTrue();
    
    // Users cannot view other users' profiles unless they're owners/managers
    expect($otherUser->can('view', $this->owner))->toBeFalse();
    
    // Owners can view other users in their company
    expect($this->owner->can('view', $this->employee))->toBeTrue();
});

it('enforces policy-based authorization for companies', function () {
    // Users can view companies they belong to
    expect($this->owner->can('view', $this->company1))->toBeTrue();
    
    // Users cannot view companies they don't belong to
    expect($this->owner->can('view', $this->company2))->toBeFalse();
    
    // Only owners can delete companies
    expect($this->owner->can('delete', $this->company1))->toBeTrue();
    expect($this->manager->can('delete', $this->company1))->toBeFalse();
});