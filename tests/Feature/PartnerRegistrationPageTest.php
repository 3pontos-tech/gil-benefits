<?php

use App\Actions\RegisterPartnerCollaboratorAction;
use App\Filament\Guest\Pages\PartnerRegistrationPage;
use App\Models\Users\Detail;
use App\Models\Users\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
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

describe('Form Validation', function () {
    test('validates required fields', function () {
        Livewire::test(PartnerRegistrationPage::class)
            ->call('submit')
            ->assertHasErrors([
                'data.name' => 'required',
                'data.rg' => 'required',
                'data.cpf' => 'required',
                'data.email' => 'required',
                'data.password' => 'required',
                'data.password_confirmation' => 'required',
                'data.partner_code' => 'required',
            ]);
    });

    test('validates name field', function () {
        Livewire::test(PartnerRegistrationPage::class)
            ->fillForm([
                'name' => str_repeat('a', 256), // Too long
            ])
            ->call('submit')
            ->assertHasErrors(['data.name' => 'max']);

        Livewire::test(PartnerRegistrationPage::class)
            ->fillForm([
                'name' => 'João Silva',
            ])
            ->call('submit')
            ->assertHasNoErrors(['data.name']);
    });

    test('validates RG field', function () {
        // Test too short RG
        Livewire::test(PartnerRegistrationPage::class)
            ->fillForm([
                'rg' => '123',
            ])
            ->call('submit')
            ->assertHasErrors(['data.rg']);

        // Test RG without numbers
        Livewire::test(PartnerRegistrationPage::class)
            ->fillForm([
                'rg' => 'ABCDEFGH',
            ])
            ->call('submit')
            ->assertHasErrors(['data.rg']);

        // Test valid RG
        Livewire::test(PartnerRegistrationPage::class)
            ->fillForm([
                'rg' => '12.345.678-9',
            ])
            ->call('submit')
            ->assertHasNoErrors(['data.rg']);
    });

    test('validates CPF field', function () {
        // Test invalid CPF
        Livewire::test(PartnerRegistrationPage::class)
            ->fillForm([
                'cpf' => '123.456.789-10',
            ])
            ->call('submit')
            ->assertHasErrors(['data.cpf']);

        // Test valid CPF
        Livewire::test(PartnerRegistrationPage::class)
            ->fillForm([
                'cpf' => '111.444.777-35',
            ])
            ->call('submit')
            ->assertHasNoErrors(['data.cpf']);
    });

    test('validates CPF uniqueness', function () {
        // Create existing user with CPF
        $user = User::factory()->create();
        Detail::factory()->create([
            'user_id' => $user->id,
            'tax_id' => '11144477735',
        ]);

        // Test duplicate CPF
        Livewire::test(PartnerRegistrationPage::class)
            ->fillForm([
                'cpf' => '111.444.777-35',
            ])
            ->call('submit')
            ->assertHasErrors(['data.cpf']);
    });

    test('validates email field', function () {
        // Test invalid email format
        Livewire::test(PartnerRegistrationPage::class)
            ->fillForm([
                'email' => 'invalid-email',
            ])
            ->call('submit')
            ->assertHasErrors(['data.email' => 'email']);

        // Test valid email
        Livewire::test(PartnerRegistrationPage::class)
            ->fillForm([
                'email' => 'test@example.com',
            ])
            ->call('submit')
            ->assertHasNoErrors(['data.email']);
    });

    test('validates email uniqueness', function () {
        // Create existing user with email
        User::factory()->create(['email' => 'test@example.com']);

        // Test duplicate email
        Livewire::test(PartnerRegistrationPage::class)
            ->fillForm([
                'email' => 'test@example.com',
            ])
            ->call('submit')
            ->assertHasErrors(['data.email' => 'unique']);
    });

    test('validates password field', function () {
        // Test short password
        Livewire::test(PartnerRegistrationPage::class)
            ->fillForm([
                'password' => '123',
            ])
            ->call('submit')
            ->assertHasErrors(['data.password']);

        // Test valid password
        Livewire::test(PartnerRegistrationPage::class)
            ->fillForm([
                'password' => 'SecurePass123!',
            ])
            ->call('submit')
            ->assertHasNoErrors(['data.password']);
    });

    test('validates password confirmation', function () {
        // Test mismatched passwords
        Livewire::test(PartnerRegistrationPage::class)
            ->fillForm([
                'password' => 'SecurePass123!',
                'password_confirmation' => 'DifferentPass123!',
            ])
            ->call('submit')
            ->assertHasErrors(['data.password_confirmation' => 'same']);

        // Test matching passwords
        Livewire::test(PartnerRegistrationPage::class)
            ->fillForm([
                'password' => 'SecurePass123!',
                'password_confirmation' => 'SecurePass123!',
            ])
            ->call('submit')
            ->assertHasNoErrors(['data.password_confirmation']);
    });

    test('validates partner code field', function () {
        // Test invalid partner code
        Livewire::test(PartnerRegistrationPage::class)
            ->fillForm([
                'partner_code' => 'INVALID',
            ])
            ->call('submit')
            ->assertHasErrors(['data.partner_code']);

        // Test valid partner code
        Livewire::test(PartnerRegistrationPage::class)
            ->fillForm([
                'partner_code' => 'TEST123',
            ])
            ->call('submit')
            ->assertHasNoErrors(['data.partner_code']);
    });
});

describe('Form State Preservation', function () {
    test('preserves form data on validation errors', function () {
        $formData = [
            'name' => 'João Silva',
            'rg' => '12.345.678-9',
            'cpf' => '111.444.777-35',
            'email' => 'joao@example.com',
            'password' => 'SecurePass123!',
            'password_confirmation' => 'SecurePass123!',
            'partner_code' => 'INVALID', // This will cause validation error
        ];

        $component = Livewire::test(PartnerRegistrationPage::class)
            ->fillForm($formData)
            ->call('submit')
            ->assertHasErrors(['data.partner_code']);

        // Check that valid data is preserved
        expect($component->get('data.name'))->toBe('João Silva');
        expect($component->get('data.rg'))->toBe('12.345.678-9');
        expect($component->get('data.cpf'))->toBe('111.444.777-35');
        expect($component->get('data.email'))->toBe('joao@example.com');
        // Password fields should be cleared for security
        expect($component->get('data.password'))->toBe('SecurePass123!');
    });

    test('clears form data on successful submission', function () {
        $formData = [
            'name' => 'João Silva',
            'rg' => '12.345.678-9',
            'cpf' => '111.444.777-35',
            'email' => 'joao@example.com',
            'password' => 'SecurePass123!',
            'password_confirmation' => 'SecurePass123!',
            'partner_code' => 'TEST123',
        ];

        $component = Livewire::test(PartnerRegistrationPage::class)
            ->fillForm($formData)
            ->call('submit');

        // Form should be cleared after successful submission
        expect($component->get('data.name'))->toBeNull();
        expect($component->get('data.email'))->toBeNull();
    });
});

describe('Error Messages', function () {
    test('displays user-friendly error messages in Portuguese', function () {
        $component = Livewire::test(PartnerRegistrationPage::class)
            ->fillForm([
                'cpf' => '123.456.789-10', // Invalid CPF
                'email' => 'invalid-email', // Invalid email
                'partner_code' => 'INVALID', // Invalid partner code
            ])
            ->call('submit');

        // Check that Portuguese error messages are displayed
        $errors = $component->instance()->getErrorBag()->getMessages();
        
        // CPF error should be in Portuguese
        expect(collect($errors)->flatten()->some(function ($message) {
            return str_contains($message, 'CPF') && str_contains($message, 'inválido');
        }))->toBeTrue();

        // Email error should be in Portuguese
        expect(collect($errors)->flatten()->some(function ($message) {
            return str_contains($message, 'e-mail') && str_contains($message, 'válido');
        }))->toBeTrue();

        // Partner code error should be in Portuguese
        expect(collect($errors)->flatten()->some(function ($message) {
            return str_contains($message, 'Código') && str_contains($message, 'parceiro');
        }))->toBeTrue();
    });
});

describe('Success Handling and User Feedback', function () {
    test('shows loading state during form submission', function () {
        $component = Livewire::test(PartnerRegistrationPage::class);
        
        // Initially not submitting
        expect($component->get('isSubmitting'))->toBeFalse();
        
        // Fill form with valid data
        $formData = [
            'name' => 'João Silva',
            'rg' => '12.345.678-9',
            'cpf' => '111.444.777-35',
            'email' => 'joao@example.com',
            'password' => 'SecurePass123!',
            'password_confirmation' => 'SecurePass123!',
            'partner_code' => 'TEST123',
        ];
        
        $component->fillForm($formData);
        
        // Mock the action to simulate processing time
        $this->mock(RegisterPartnerCollaboratorAction::class, function ($mock) {
            $user = User::factory()->make(['name' => 'João Silva', 'email' => 'joao@example.com']);
            $mock->shouldReceive('execute')->andReturn(
                \App\DTO\RegistrationResult::success($user, $this->company)
            );
        });
        
        $component->call('submit');
        
        // Should show success state
        expect($component->get('registrationSuccess'))->toBeTrue();
        expect($component->get('successMessage'))->toContain('João Silva');
        expect($component->get('successMessage'))->toContain('joao@example.com');
    });

    test('displays success message with user and company details', function () {
        $formData = [
            'name' => 'Maria Santos',
            'rg' => '98.765.432-1',
            'cpf' => '111.444.777-35',
            'email' => 'maria@example.com',
            'password' => 'SecurePass123!',
            'password_confirmation' => 'SecurePass123!',
            'partner_code' => 'TEST123',
        ];

        $component = Livewire::test(PartnerRegistrationPage::class)
            ->fillForm($formData)
            ->call('submit');

        // Check success state
        expect($component->get('registrationSuccess'))->toBeTrue();
        expect($component->get('successMessage'))->not()->toBeNull();
        expect($component->get('redirectUrl'))->toBe('/app/login');
        
        // Success message should contain user details
        $successMessage = $component->get('successMessage');
        expect($successMessage)->toContain('Maria Santos');
        expect($successMessage)->toContain('maria@example.com');
        expect($successMessage)->toContain($this->company->name);
    });

    test('form actions show loading state during submission', function () {
        $component = Livewire::test(PartnerRegistrationPage::class);
        
        // Set submitting state
        $component->set('isSubmitting', true);
        
        $actions = $component->instance()->getFormActions();
        $submitAction = $actions[0];
        
        // Check that action shows loading state
        expect($submitAction->getLabel())->toBe('Processando...');
        expect($submitAction->isDisabled())->toBeTrue();
    });

    test('form actions are disabled after successful registration', function () {
        $component = Livewire::test(PartnerRegistrationPage::class);
        
        // Set success state
        $component->set('registrationSuccess', true);
        
        $actions = $component->instance()->getFormActions();
        $submitAction = $actions[0];
        
        // Check that action is disabled after success
        expect($submitAction->isDisabled())->toBeTrue();
    });

    test('redirect to login functionality works', function () {
        $component = Livewire::test(PartnerRegistrationPage::class);
        
        $component->call('redirectToLogin')
            ->assertRedirect('/app/login');
    });

    test('preserves form data on validation errors', function () {
        $formData = [
            'name' => 'João Silva',
            'rg' => '12.345.678-9',
            'cpf' => '111.444.777-35',
            'email' => 'joao@example.com',
            'password' => 'SecurePass123!',
            'password_confirmation' => 'SecurePass123!',
            'partner_code' => 'INVALID_CODE', // This will cause validation error
        ];

        $component = Livewire::test(PartnerRegistrationPage::class)
            ->fillForm($formData)
            ->call('submit');

        // Should have validation errors
        $component->assertHasErrors(['data.partner_code']);
        
        // Form data should be preserved (except passwords)
        expect($component->get('data.name'))->toBe('João Silva');
        expect($component->get('data.rg'))->toBe('12.345.678-9');
        expect($component->get('data.cpf'))->toBe('111.444.777-35');
        expect($component->get('data.email'))->toBe('joao@example.com');
        expect($component->get('data.partner_code'))->toBe('INVALID_CODE');
        
        // Should not be in success state
        expect($component->get('registrationSuccess'))->toBeFalse();
        expect($component->get('isSubmitting'))->toBeFalse();
    });

    test('shows appropriate success notification', function () {
        $formData = [
            'name' => 'João Silva',
            'rg' => '12.345.678-9',
            'cpf' => '111.444.777-35',
            'email' => 'joao@example.com',
            'password' => 'SecurePass123!',
            'password_confirmation' => 'SecurePass123!',
            'partner_code' => 'TEST123',
        ];

        $component = Livewire::test(PartnerRegistrationPage::class)
            ->fillForm($formData)
            ->call('submit');

        // Should show success notification
        $component->assertNotified('Cadastro realizado com sucesso!');
    });

    test('shows appropriate error notification on registration failure', function () {
        // Create a company with different partner code to cause validation failure
        $formData = [
            'name' => 'João Silva',
            'rg' => '12.345.678-9',
            'cpf' => '111.444.777-35',
            'email' => 'joao@example.com',
            'password' => 'SecurePass123!',
            'password_confirmation' => 'SecurePass123!',
            'partner_code' => 'INVALID_CODE', // This will fail validation
        ];

        $component = Livewire::test(PartnerRegistrationPage::class)
            ->fillForm($formData)
            ->call('submit');

        // Should have validation errors for partner code
        $component->assertHasErrors(['data.partner_code']);
        
        // Should not be in success state
        expect($component->get('registrationSuccess'))->toBeFalse();
    });

    test('resets loading state after completion', function () {
        $formData = [
            'name' => 'João Silva',
            'rg' => '12.345.678-9',
            'cpf' => '111.444.777-35',
            'email' => 'joao@example.com',
            'password' => 'SecurePass123!',
            'password_confirmation' => 'SecurePass123!',
            'partner_code' => 'TEST123',
        ];

        $component = Livewire::test(PartnerRegistrationPage::class)
            ->fillForm($formData)
            ->call('submit');

        // Loading state should be reset after completion
        expect($component->get('isSubmitting'))->toBeFalse();
    });

    test('success state includes redirect URL', function () {
        $formData = [
            'name' => 'João Silva',
            'rg' => '12.345.678-9',
            'cpf' => '111.444.777-35',
            'email' => 'joao@example.com',
            'password' => 'SecurePass123!',
            'password_confirmation' => 'SecurePass123!',
            'partner_code' => 'TEST123',
        ];

        $component = Livewire::test(PartnerRegistrationPage::class)
            ->fillForm($formData)
            ->call('submit');

        // Should set redirect URL on success
        expect($component->get('redirectUrl'))->toBe('/app/login');
        expect($component->get('registrationSuccess'))->toBeTrue();
    });

    test('success message contains all required information', function () {
        $formData = [
            'name' => 'Ana Costa',
            'rg' => '98.765.432-1',
            'cpf' => '111.444.777-35',
            'email' => 'ana@example.com',
            'password' => 'SecurePass123!',
            'password_confirmation' => 'SecurePass123!',
            'partner_code' => 'TEST123',
        ];

        $component = Livewire::test(PartnerRegistrationPage::class)
            ->fillForm($formData)
            ->call('submit');

        $successMessage = $component->get('successMessage');
        
        // Success message should contain user name, company name, and email
        expect($successMessage)->toContain('Ana Costa');
        expect($successMessage)->toContain('ana@example.com');
        expect($successMessage)->toContain($this->company->name);
        expect($successMessage)->toContain('cadastro foi concluído com sucesso');
    });
});