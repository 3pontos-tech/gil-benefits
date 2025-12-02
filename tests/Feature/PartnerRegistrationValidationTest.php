<?php

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
        'partner_code' => 'VALID123',
    ]);
});

describe('Advanced Validation Scenarios', function () {
    test('validates CPF with various invalid formats', function () {
        $invalidCpfs = [
            '000.000.000-00', // All zeros
            '111.111.111-11', // All same digits
            '123.456.789-10', // Invalid check digits
            '12345678901',     // Too short (missing digit)
            '1234567890123',   // Too long
            'abc.def.ghi-jk',  // Non-numeric
            '   ',             // Only spaces
            '',                // Empty
        ];

        foreach ($invalidCpfs as $cpf) {
            Livewire::test(PartnerRegistrationPage::class)
                ->fillForm([
                    'name' => 'João Silva',
                    'rg' => '12.345.678-9',
                    'cpf' => $cpf,
                    'email' => 'joao@example.com',
                    'password' => 'SecurePass123!',
                    'password_confirmation' => 'SecurePass123!',
                    'partner_code' => 'VALID123',
                ])
                ->call('submit')
                ->assertHasErrors(['data.cpf']);
        }
    });

    test('validates RG with various invalid formats', function () {
        $invalidRgs = [
            '123',           // Too short
            '1234567890123456', // Too long
            'ABCDEFGH',      // No numbers
            '   ',           // Only spaces
            '',              // Empty
        ];

        foreach ($invalidRgs as $rg) {
            Livewire::test(PartnerRegistrationPage::class)
                ->fillForm([
                    'name' => 'João Silva',
                    'rg' => $rg,
                    'cpf' => '111.444.777-35',
                    'email' => 'joao@example.com',
                    'password' => 'SecurePass123!',
                    'password_confirmation' => 'SecurePass123!',
                    'partner_code' => 'VALID123',
                ])
                ->call('submit')
                ->assertHasErrors(['data.rg']);
        }
    });

    test('validates email with various invalid formats', function () {
        $invalidEmails = [
            'invalid-email',
            '@example.com',
            'user@',
            'user name@example.com', // Space in email
            '',
        ];

        foreach ($invalidEmails as $email) {
            $component = Livewire::test(PartnerRegistrationPage::class)
                ->fillForm([
                    'name' => 'João Silva',
                    'rg' => '12.345.678-9',
                    'cpf' => '111.444.777-35',
                    'email' => $email,
                    'password' => 'SecurePass123!',
                    'password_confirmation' => 'SecurePass123!',
                    'partner_code' => 'VALID123',
                ])
                ->call('submit');

            // Check if there are any validation errors for email
            $errors = $component->instance()->getErrorBag();
            expect($errors->has('data.email'))->toBeTrue("Email '{$email}' should be invalid but passed validation");
        }
    });

    test('validates password with various weak passwords', function () {
        $weakPasswords = [
            '123',           // Too short
            'password',      // No numbers, no symbols, no uppercase
            '12345678',      // Only numbers
            'PASSWORD',      // Only uppercase
            'password123',   // No uppercase, no symbols
            'Password',      // No numbers, no symbols
        ];

        foreach ($weakPasswords as $password) {
            Livewire::test(PartnerRegistrationPage::class)
                ->fillForm([
                    'name' => 'João Silva',
                    'rg' => '12.345.678-9',
                    'cpf' => '111.444.777-35',
                    'email' => 'joao@example.com',
                    'password' => $password,
                    'password_confirmation' => $password,
                    'partner_code' => 'VALID123',
                ])
                ->call('submit')
                ->assertHasErrors(['data.password']);
        }
    });

    test('validates partner code with various invalid formats', function () {
        $invalidPartnerCodes = [
            'NONEXISTENT',
            '',
            '   ',
            str_repeat('A', 51), // Too long
        ];

        foreach ($invalidPartnerCodes as $partnerCode) {
            Livewire::test(PartnerRegistrationPage::class)
                ->fillForm([
                    'name' => 'João Silva',
                    'rg' => '12.345.678-9',
                    'cpf' => '111.444.777-35',
                    'email' => 'joao@example.com',
                    'password' => 'SecurePass123!',
                    'password_confirmation' => 'SecurePass123!',
                    'partner_code' => $partnerCode,
                ])
                ->call('submit')
                ->assertHasErrors(['data.partner_code']);
        }
    });
});

describe('Cross-field Validation', function () {
    test('validates password confirmation mismatch', function () {
        Livewire::test(PartnerRegistrationPage::class)
            ->fillForm([
                'name' => 'João Silva',
                'rg' => '12.345.678-9',
                'cpf' => '111.444.777-35',
                'email' => 'joao@example.com',
                'password' => 'SecurePass123!',
                'password_confirmation' => 'DifferentPass123!',
                'partner_code' => 'VALID123',
            ])
            ->call('submit')
            ->assertHasErrors(['data.password_confirmation']);
    });

    test('validates duplicate email across different users', function () {
        // Create existing user
        User::factory()->create(['email' => 'existing@example.com']);

        Livewire::test(PartnerRegistrationPage::class)
            ->fillForm([
                'name' => 'João Silva',
                'rg' => '12.345.678-9',
                'cpf' => '111.444.777-35',
                'email' => 'existing@example.com',
                'password' => 'SecurePass123!',
                'password_confirmation' => 'SecurePass123!',
                'partner_code' => 'VALID123',
            ])
            ->call('submit')
            ->assertHasErrors(['data.email']);
    });

    test('validates duplicate CPF across different users', function () {
        // Create existing user with CPF
        $user = User::factory()->create();
        Detail::factory()->create([
            'user_id' => $user->id,
            'tax_id' => '11144477735',
        ]);

        Livewire::test(PartnerRegistrationPage::class)
            ->fillForm([
                'name' => 'João Silva',
                'rg' => '12.345.678-9',
                'cpf' => '111.444.777-35', // Same CPF, different format
                'email' => 'joao@example.com',
                'password' => 'SecurePass123!',
                'password_confirmation' => 'SecurePass123!',
                'partner_code' => 'VALID123',
            ])
            ->call('submit')
            ->assertHasErrors(['data.cpf']);
    });
});

describe('Boundary Value Testing', function () {
    test('validates maximum length fields', function () {
        Livewire::test(PartnerRegistrationPage::class)
            ->fillForm([
                'name' => str_repeat('A', 255), // Exactly at limit
                'rg' => '123456789012345', // 15 chars - at RG limit
                'cpf' => '111.444.777-35',
                'email' => 'a@example.com',
                'password' => 'SecurePass123!',
                'password_confirmation' => 'SecurePass123!',
                'partner_code' => str_repeat('A', 50), // At limit but invalid
            ])
            ->call('submit')
            ->assertHasNoErrors(['data.name', 'data.rg'])
            ->assertHasErrors(['data.partner_code']); // Invalid partner code
    });

    test('validates fields exceeding maximum length', function () {
        Livewire::test(PartnerRegistrationPage::class)
            ->fillForm([
                'name' => str_repeat('A', 256), // Over limit
                'rg' => '1234567890123456', // 16 chars - over RG limit
                'cpf' => '111.444.777-35',
                'email' => 'joao@example.com',
                'password' => 'SecurePass123!',
                'password_confirmation' => 'SecurePass123!',
                'partner_code' => str_repeat('A', 51), // Over limit
            ])
            ->call('submit')
            ->assertHasErrors(['data.name', 'data.rg', 'data.partner_code']);
    });
});

describe('Special Characters and Encoding', function () {
    test('handles names with special characters', function () {
        $namesWithSpecialChars = [
            'José da Silva',
            'María González',
            'François Müller',
            'João Paulo II',
            'Ana-Clara Santos',
            "O'Connor Smith",
        ];

        foreach ($namesWithSpecialChars as $name) {
            Livewire::test(PartnerRegistrationPage::class)
                ->fillForm([
                    'name' => $name,
                    'rg' => '12.345.678-9',
                    'cpf' => '111.444.777-35',
                    'email' => 'test@example.com',
                    'password' => 'SecurePass123!',
                    'password_confirmation' => 'SecurePass123!',
                    'partner_code' => 'VALID123',
                ])
                ->call('submit')
                ->assertHasNoErrors(['data.name']);
        }
    });

    test('handles RG with various valid formats', function () {
        $validRgFormats = [
            '12.345.678-9',
            '12345678X',
            'MG-12.345.678',
            'SP123456789',
            '12 345 678 9',
            '12/345/678/9',
        ];

        foreach ($validRgFormats as $rg) {
            Livewire::test(PartnerRegistrationPage::class)
                ->fillForm([
                    'name' => 'João Silva',
                    'rg' => $rg,
                    'cpf' => '111.444.777-35',
                    'email' => 'test@example.com',
                    'password' => 'SecurePass123!',
                    'password_confirmation' => 'SecurePass123!',
                    'partner_code' => 'VALID123',
                ])
                ->call('submit')
                ->assertHasNoErrors(['data.rg']);
        }
    });
});

describe('Case Sensitivity Tests', function () {
    test('partner code validation is case insensitive', function () {
        $caseVariations = [
            'valid123',
            'VALID123',
            'Valid123',
            'vAlId123',
        ];

        foreach ($caseVariations as $partnerCode) {
            Livewire::test(PartnerRegistrationPage::class)
                ->fillForm([
                    'name' => 'João Silva',
                    'rg' => '12.345.678-9',
                    'cpf' => '111.444.777-35',
                    'email' => 'test@example.com',
                    'password' => 'SecurePass123!',
                    'password_confirmation' => 'SecurePass123!',
                    'partner_code' => $partnerCode,
                ])
                ->call('submit')
                ->assertHasNoErrors(['data.partner_code']);
        }
    });

    test('email validation handles exact duplicates', function () {
        User::factory()->create(['email' => 'test@example.com']);

        Livewire::test(PartnerRegistrationPage::class)
            ->fillForm([
                'name' => 'João Silva',
                'rg' => '12.345.678-9',
                'cpf' => '111.444.777-35',
                'email' => 'test@example.com', // Exact duplicate
                'password' => 'SecurePass123!',
                'password_confirmation' => 'SecurePass123!',
                'partner_code' => 'VALID123',
            ])
            ->call('submit')
            ->assertHasErrors(['data.email']); // Should detect duplicate
    });
});
