<?php

use App\Models\Users\User;
use TresPontosTech\Company\Models\Company;

use function Pest\Laravel\assertDatabaseHas;

describe('Partner Registration Flow', function () {
    beforeEach(function () {
        // Create a company with partner code for testing
        $this->company = Company::factory()->create([
            'name' => 'Test Partner Company',
            'partner_code' => 'TEST123',
        ]);
    });

    it('completes full partner registration flow successfully', function () {
        $page = visit('/partners');

        $page->assertSee('Cadastro de Colaborador Parceiro')
            ->assertNoJavaScriptErrors()
            ->assertSee('Dados Pessoais')
            ->assertSee('Dados de Acesso')
            ->assertSee('Dados da Empresa');

        // Fill personal data section
        $page->type('[name="data.name"]', 'João Silva Santos')
            ->type('[name="data.rg"]', '12.345.678-9')
            ->type('[name="data.cpf"]', '111.444.777-35')
            ->type('[name="data.email"]', 'joao.silva@example.com');

        // Fill access data section
        $page->type('[name="data.password"]', 'SecurePass123!')
            ->type('[name="data.password_confirmation"]', 'SecurePass123!');

        // Fill company data section
        $page->type('[name="data.partner_code"]', 'TEST123');

        // Submit the form
        $page->click('Cadastrar Colaborador')
            ->waitFor('.fi-no-title', 5) // Wait for notification
            ->assertSee('Cadastro realizado com sucesso!')
            ->assertSee('João Silva Santos')
            ->assertSee('Test Partner Company')
            ->assertSee('joao.silva@example.com');

        // Verify database records were created
        assertDatabaseHas(User::class, [
            'name' => 'João Silva Santos',
            'email' => 'joao.silva@example.com',
        ]);

        // Wait for automatic redirect
        $page->waitForLocation('/app/login', 10);
        $page->assertPathIs('/app/login');
    });

    it('shows validation errors for invalid data', function () {
        $page = visit('/partners');

        // Try to submit empty form
        $page->click('Cadastrar Colaborador')
            ->assertSee('O nome completo é obrigatório')
            ->assertSee('O RG é obrigatório')
            ->assertSee('O CPF é obrigatório')
            ->assertSee('O e-mail é obrigatório')
            ->assertSee('A senha é obrigatória')
            ->assertSee('A confirmação de senha é obrigatória')
            ->assertSee('O código do parceiro é obrigatório');

        // Fill with invalid data
        $page->type('[name="data.name"]', 'Jo') // Too short
            ->type('[name="data.rg"]', '123') // Invalid RG
            ->type('[name="data.cpf"]', '123.456.789-10') // Invalid CPF
            ->type('[name="data.email"]', 'invalid-email') // Invalid email
            ->type('[name="data.password"]', '123') // Weak password
            ->type('[name="data.password_confirmation"]', '456') // Mismatched
            ->type('[name="data.partner_code"]', 'INVALID'); // Invalid partner code

        $page->click('Cadastrar Colaborador')
            ->assertSee('O nome não pode ter menos de')
            ->assertSee('CPF inválido')
            ->assertSee('Digite um e-mail válido')
            ->assertSee('A senha deve ter pelo menos 8 caracteres')
            ->assertSee('As senhas não coincidem')
            ->assertSee('Código do parceiro inválido');
    });

    it('preserves form data on validation errors', function () {
        $page = visit('/partners');

        // Fill form with mostly valid data but invalid partner code
        $page->type('[name="data.name"]', 'Maria Santos')
            ->type('[name="data.rg"]', '98.765.432-1')
            ->type('[name="data.cpf"]', '111.444.777-35')
            ->type('[name="data.email"]', 'maria@example.com')
            ->type('[name="data.password"]', 'SecurePass123!')
            ->type('[name="data.password_confirmation"]', 'SecurePass123!')
            ->type('[name="data.partner_code"]', 'INVALID');

        $page->click('Cadastrar Colaborador')
            ->assertSee('Código do parceiro inválido');

        // Verify form data is preserved
        $page->assertValue('[name="data.name"]', 'Maria Santos')
            ->assertValue('[name="data.rg"]', '98.765.432-1')
            ->assertValue('[name="data.cpf"]', '111.444.777-35')
            ->assertValue('[name="data.email"]', 'maria@example.com')
            ->assertValue('[name="data.partner_code"]', 'INVALID');

        // Password fields should be preserved for user convenience
        $page->assertValue('[name="data.password"]', 'SecurePass123!')
            ->assertValue('[name="data.password_confirmation"]', 'SecurePass123!');
    });

    it('shows loading state during form submission', function () {
        $page = visit('/partners');

        // Fill valid form data
        $page->type('[name="data.name"]', 'Carlos Oliveira')
            ->type('[name="data.rg"]', '11.222.333-4')
            ->type('[name="data.cpf"]', '111.444.777-35')
            ->type('[name="data.email"]', 'carlos@example.com')
            ->type('[name="data.password"]', 'SecurePass123!')
            ->type('[name="data.password_confirmation"]', 'SecurePass123!')
            ->type('[name="data.partner_code"]', 'TEST123');

        // Click submit and check loading state
        $page->click('Cadastrar Colaborador');

        // The button should show loading state briefly
        $page->assertSee('Processando...')
            ->assertElementAttribute('button[type="submit"]', 'disabled', 'true');
    });

    it('handles duplicate email validation', function () {
        // Create existing user
        User::factory()->create(['email' => 'existing@example.com']);

        $page = visit('/partners');

        $page->type('[name="data.name"]', 'Ana Costa')
            ->type('[name="data.rg"]', '55.666.777-8')
            ->type('[name="data.cpf"]', '111.444.777-35')
            ->type('[name="data.email"]', 'existing@example.com') // Duplicate email
            ->type('[name="data.password"]', 'SecurePass123!')
            ->type('[name="data.password_confirmation"]', 'SecurePass123!')
            ->type('[name="data.partner_code"]', 'TEST123');

        $page->click('Cadastrar Colaborador')
            ->assertSee('Este e-mail já está cadastrado no sistema');
    });

    it('validates CPF format and uniqueness', function () {
        $page = visit('/partners');

        // Test invalid CPF format
        $page->type('[name="data.cpf"]', '123.456.789-00') // Invalid CPF
            ->blur('[name="data.cpf"]')
            ->assertSee('CPF inválido');

        // Clear and test valid CPF
        $page->clear('[name="data.cpf"]')
            ->type('[name="data.cpf"]', '111.444.777-35') // Valid CPF
            ->blur('[name="data.cpf"]');

        // Should not show error for valid CPF
        $page->assertDontSee('CPF inválido');
    });

    it('validates partner code in real-time', function () {
        $page = visit('/partners');

        // Test invalid partner code
        $page->type('[name="data.partner_code"]', 'INVALID')
            ->blur('[name="data.partner_code"]')
            ->assertSee('Código do parceiro inválido');

        // Clear and test valid partner code
        $page->clear('[name="data.partner_code"]')
            ->type('[name="data.partner_code"]', 'TEST123')
            ->blur('[name="data.partner_code"]');

        // Should not show error for valid partner code
        $page->assertDontSee('Código do parceiro inválido');
    });

    it('shows password strength requirements', function () {
        $page = visit('/partners');

        $page->assertSee('A senha deve conter pelo menos 8 caracteres')
            ->assertSee('incluindo letras maiúsculas, minúsculas, números e símbolos');

        // Test weak password
        $page->type('[name="data.password"]', 'weak')
            ->blur('[name="data.password"]')
            ->assertSee('A senha deve ter pelo menos 8 caracteres');
    });

    it('validates password confirmation matching', function () {
        $page = visit('/partners');

        $page->type('[name="data.password"]', 'SecurePass123!')
            ->type('[name="data.password_confirmation"]', 'DifferentPass123!')
            ->blur('[name="data.password_confirmation"]')
            ->assertSee('As senhas não coincidem');

        // Fix password confirmation
        $page->clear('[name="data.password_confirmation"]')
            ->type('[name="data.password_confirmation"]', 'SecurePass123!')
            ->blur('[name="data.password_confirmation"]');

        // Should not show error for matching passwords
        $page->assertDontSee('As senhas não coincidem');
    });

    it('displays helpful field descriptions', function () {
        $page = visit('/partners');

        $page->assertSee('Preencha os dados do colaborador')
            ->assertSee('Defina a senha de acesso')
            ->assertSee('Informe o código do parceiro')
            ->assertSee('Este código foi fornecido pela empresa parceira');
    });

    it('supports keyboard navigation and shortcuts', function () {
        $page = visit('/partners');

        // Fill form using tab navigation
        $page->type('[name="data.name"]', 'Pedro Almeida')
            ->press('Tab')
            ->type('[name="data.rg"]', '99.888.777-6')
            ->press('Tab')
            ->type('[name="data.cpf"]', '111.444.777-35')
            ->press('Tab')
            ->type('[name="data.email"]', 'pedro@example.com')
            ->press('Tab')
            ->type('[name="data.password"]', 'SecurePass123!')
            ->press('Tab')
            ->type('[name="data.password_confirmation"]', 'SecurePass123!')
            ->press('Tab')
            ->type('[name="data.partner_code"]', 'TEST123');

        // Use keyboard shortcut to submit (Ctrl+S or Cmd+S)
        $page->keys('[name="data.partner_code"]', ['{Control}', 's']);

        $page->waitFor('.fi-no-title', 5)
            ->assertSee('Cadastro realizado com sucesso!');
    });
})->group('browser', 'partner-registration');
