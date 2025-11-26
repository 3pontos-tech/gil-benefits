<?php

use App\Filament\Guest\Pages\PartnerRegistrationPage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use TresPontosTech\Company\Models\Company;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Create a company with partner code for testing
    $this->company = Company::factory()->create([
        'partner_code' => 'TEST123',
    ]);
});

test('partner registration page class exists and has correct properties', function () {
    expect(class_exists(PartnerRegistrationPage::class))->toBeTrue();
    
    $reflection = new ReflectionClass(PartnerRegistrationPage::class);
    expect($reflection->hasProperty('view'))->toBeTrue();
    expect($reflection->hasMethod('form'))->toBeTrue();
    expect($reflection->hasMethod('submit'))->toBeTrue();
    expect($reflection->hasMethod('validatePartnerCode'))->toBeTrue();
});

test('partner code validation works', function () {
    $page = new PartnerRegistrationPage();
    
    // Test valid partner code
    expect($page->validatePartnerCode('TEST123'))->toBeTrue();
    
    // Test invalid partner code
    expect($page->validatePartnerCode('INVALID'))->toBeFalse();
    
    // Test case insensitive validation
    expect($page->validatePartnerCode('test123'))->toBeTrue();
    
    // Test empty partner code
    expect($page->validatePartnerCode(''))->toBeFalse();
});