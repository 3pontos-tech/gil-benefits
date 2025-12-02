<?php

use App\Models\Users\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use TresPontosTech\Company\Models\Company;

uses(RefreshDatabase::class);

it('debugs authorization system', function () {
    // Create test data
    $company = Company::factory()->create(['name' => 'Test Company']);
    $owner = User::factory()->create(['email' => 'owner@test.com']);
    
    // Attach user to company with owner role
    $owner->companies()->attach($company, ['role' => 'owner']);
    
    // Refresh the user to load relationships
    $owner->refresh();
    
    // Debug: Check what role is stored
    $userRole = $owner->companies()->where('companies.id', $company->id)->first()?->pivot?->role;
    echo "Stored role: " . $userRole . "\n";
    
    // Debug: Check if user has companies
    echo "User companies count: " . $owner->companies->count() . "\n";
    
    // Test gate
    $result = Gate::forUser($owner)->allows('manage-company-settings', $company);
    echo "Gate result: " . ($result ? 'true' : 'false') . "\n";
    
    expect($result)->toBeTrue();
});