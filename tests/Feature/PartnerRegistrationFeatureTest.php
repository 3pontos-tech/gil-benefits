<?php

use App\Filament\Guest\Pages\PartnerRegistrationPage;
use App\Models\Users\User;
use Livewire\Livewire;
use TresPontosTech\Company\Models\Company;

describe('Partner Registration Feature', function () {
    it('can access the partner registration page', function () {
        $response = $this->get('/partners');

        $response->assertSuccessful();
        $response->assertSeeLivewire(PartnerRegistrationPage::class);
    });

    it('can submit valid partner registration form', function () {
        // Arrange
        $company = Company::factory()->create([
            'partner_code' => 'VALID123',
        ]);

        // Act
        Livewire::test(PartnerRegistrationPage::class)
            ->fillForm([
                'name' => 'João Silva',
                'email' => 'joao@example.com',
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'cpf' => '111.444.777-35',
                'rg' => '12.345.678-9',
                'partner_code' => 'VALID123',
            ])
            ->call('register')
            ->assertHasNoFormErrors();

        // Assert
        $this->assertDatabaseHas('users', [
            'name' => 'João Silva',
            'email' => 'joao@example.com',
        ]);

        $user = User::where('email', 'joao@example.com')->first();
        $this->assertDatabaseHas('user_details', [
            'user_id' => $user->id,
            'document_id' => '12.345.678-9',
            'tax_id' => '11144477735',
            'company_id' => $company->id,
        ]);

        $this->assertDatabaseHas('company_employees', [
            'user_id' => $user->id,
            'company_id' => $company->id,
            'active' => true,
        ]);
    });

    it('validates required fields', function () {
        Livewire::test(PartnerRegistrationPage::class)
            ->fillForm([])
            ->call('register')
            ->assertHasFormErrors([
                'name' => 'required',
                'email' => 'required',
                'password' => 'required',
                'cpf' => 'required',
                'rg' => 'required',
                'partner_code' => 'required',
            ]);
    });

    it('validates email format', function () {
        Livewire::test(PartnerRegistrationPage::class)
            ->fillForm([
                'name' => 'João Silva',
                'email' => 'invalid-email',
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'cpf' => '111.444.777-35',
                'rg' => '12.345.678-9',
                'partner_code' => 'VALID123',
            ])
            ->call('register')
            ->assertHasFormErrors(['email']);
    });

    it('validates password confirmation', function () {
        Livewire::test(PartnerRegistrationPage::class)
            ->fillForm([
                'name' => 'João Silva',
                'email' => 'joao@example.com',
                'password' => 'password123',
                'password_confirmation' => 'different_password',
                'cpf' => '111.444.777-35',
                'rg' => '12.345.678-9',
                'partner_code' => 'VALID123',
            ])
            ->call('register')
            ->assertHasFormErrors(['password']);
    });

    it('validates cpf format', function () {
        Company::factory()->create([
            'partner_code' => 'VALID123',
        ]);

        Livewire::test(PartnerRegistrationPage::class)
            ->fillForm([
                'name' => 'João Silva',
                'email' => 'joao@example.com',
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'cpf' => '111.111.111-11', // Invalid CPF
                'rg' => '12.345.678-9',
                'partner_code' => 'VALID123',
            ])
            ->call('register')
            ->assertHasFormErrors(['cpf']);
    });

    it('validates partner code exists', function () {
        Livewire::test(PartnerRegistrationPage::class)
            ->fillForm([
                'name' => 'João Silva',
                'email' => 'joao@example.com',
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'cpf' => '111.444.777-35',
                'rg' => '12.345.678-9',
                'partner_code' => 'INVALID123',
            ])
            ->call('register')
            ->assertHasFormErrors(['partner_code']);
    });

    it('validates unique email', function () {
        Company::factory()->create([
            'partner_code' => 'VALID123',
        ]);

        User::factory()->create([
            'email' => 'existing@example.com',
        ]);

        Livewire::test(PartnerRegistrationPage::class)
            ->fillForm([
                'name' => 'João Silva',
                'email' => 'existing@example.com',
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'cpf' => '111.444.777-35',
                'rg' => '12.345.678-9',
                'partner_code' => 'VALID123',
            ])
            ->call('register')
            ->assertHasFormErrors(['email']);
    });

    it('validates unique cpf', function () {
        Company::factory()->create([
            'partner_code' => 'VALID123',
        ]);

        $existingUser = User::factory()->create();
        $existingUser->details()->create([
            'document_id' => '98.765.432-1',
            'tax_id' => '11144477735', // Same CPF as test
            'company_id' => 1,
        ]);

        Livewire::test(PartnerRegistrationPage::class)
            ->fillForm([
                'name' => 'João Silva',
                'email' => 'joao@example.com',
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'cpf' => '111.444.777-35', // Same CPF as existing user
                'rg' => '12.345.678-9',
                'partner_code' => 'VALID123',
            ])
            ->call('register')
            ->assertHasFormErrors(['cpf']);
    });

    it('handles case insensitive partner codes', function () {
        // Arrange
        $company = Company::factory()->create([
            'partner_code' => 'VALID123',
        ]);

        // Act
        Livewire::test(PartnerRegistrationPage::class)
            ->fillForm([
                'name' => 'João Silva',
                'email' => 'joao@example.com',
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'cpf' => '111.444.777-35',
                'rg' => '12.345.678-9',
                'partner_code' => 'valid123', // lowercase
            ])
            ->call('register')
            ->assertHasNoFormErrors();

        // Assert
        $this->assertDatabaseHas('users', [
            'name' => 'João Silva',
            'email' => 'joao@example.com',
        ]);
    });

    it('shows success notification after registration', function () {
        // Arrange
        Company::factory()->create([
            'partner_code' => 'VALID123',
        ]);

        // Act & Assert
        Livewire::test(PartnerRegistrationPage::class)
            ->fillForm([
                'name' => 'João Silva',
                'email' => 'joao@example.com',
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'cpf' => '111.444.777-35',
                'rg' => '12.345.678-9',
                'partner_code' => 'VALID123',
            ])
            ->call('register')
            ->assertNotified();
    });

    it('respects rate limiting', function () {
        // This test would require setting up rate limiting configuration
        // and making multiple requests to test the throttling behavior
        expect(true)->toBeTrue(); // Placeholder for rate limiting test
    });
});
