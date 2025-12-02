<?php

use App\Models\Users\User;
use TresPontosTech\Company\Models\Company;

describe('Company Management Workflows', function () {
    beforeEach(function () {
        $this->admin = User::factory()->admin()->create([
            'name' => 'System Admin',
            'email' => 'admin@system.com',
            'password' => bcrypt('admin123'),
        ]);

        $this->company = Company::factory()->create([
            'name' => 'Test Company',
            'partner_code' => 'TEST123',
        ]);
    });

    it('allows admin to view companies list', function () {
        $this->actingAs($this->admin);

        $page = visit('/admin/companies');

        $page->assertSee('Companies')
            ->assertNoJavaScriptErrors()
            ->assertSee($this->company->name)
            ->assertSee($this->company->partner_code);
    });

    it('allows admin to create new company', function () {
        $this->actingAs($this->admin);

        $page = visit('/admin/companies');

        $page->click('Criar Company')
            ->waitForLocation('/admin/companies/create', 5)
            ->assertSee('Criar Company');

        $page->type('[name="name"]', 'Nova Empresa Ltda')
            ->type('[name="partner_code"]', 'NOVA123')
            ->type('[name="email"]', 'contato@novaempresa.com')
            ->type('[name="phone"]', '(11) 99999-9999')
            ->click('Criar');

        $page->assertSee('Criado')
            ->waitForLocation('/admin/companies', 5);

        // Verify company appears in list
        $page->assertSee('Nova Empresa Ltda')
            ->assertSee('NOVA123');
    });

    it('validates company creation form', function () {
        $this->actingAs($this->admin);

        $page = visit('/admin/companies/create');

        // Try to submit empty form
        $page->click('Criar')
            ->assertSee('O campo nome é obrigatório')
            ->assertSee('O campo código do parceiro é obrigatório');

        // Try duplicate partner code
        $page->type('[name="name"]', 'Empresa Duplicada')
            ->type('[name="partner_code"]', 'TEST123') // Existing code
            ->click('Criar')
            ->assertSee('O código do parceiro já está em uso');
    });

    it('allows admin to edit company details', function () {
        $this->actingAs($this->admin);

        $page = visit('/admin/companies');

        $page->click($this->company->name)
            ->waitForLocation("/admin/companies/{$this->company->id}/edit", 5)
            ->assertSee('Editar Company');

        $page->clear('[name="name"]')
            ->type('[name="name"]', 'Updated Company Name')
            ->clear('[name="email"]')
            ->type('[name="email"]', 'updated@company.com')
            ->click('Salvar');

        $page->assertSee('Salvo')
            ->waitForLocation('/admin/companies', 5);

        // Verify changes in list
        $page->assertSee('Updated Company Name')
            ->assertSee('updated@company.com');
    });

    it('allows admin to delete company', function () {
        $this->actingAs($this->admin);

        $page = visit("/admin/companies/{$this->company->id}/edit");

        $page->click('Excluir')
            ->assertSee('Excluir Company')
            ->assertSee('Você tem certeza que gostaria de fazer isso?')
            ->click('Excluir');

        $page->assertSee('Excluído')
            ->waitForLocation('/admin/companies', 5);

        // Company should not appear in list
        $page->assertDontSee($this->company->name);
    });

    it('allows filtering companies by status', function () {
        $this->actingAs($this->admin);

        // Create active and inactive companies
        $activeCompany = Company::factory()->create(['name' => 'Active Company']);
        $inactiveCompany = Company::factory()->create(['name' => 'Inactive Company']);
        $inactiveCompany->delete(); // Soft delete

        $page = visit('/admin/companies');

        // Default view should show active companies
        $page->assertSee('Active Company')
            ->assertDontSee('Inactive Company');

        // Filter to show deleted companies
        $page->click('.fi-dropdown.fi-ta-filters-dropdown')
            ->select('tableFiltersForm.trashed.value', 'Somente registros excluídos')
            ->click('Aplicar filtros');

        $page->assertSee('Inactive Company')
            ->assertDontSee('Active Company');
    });

    it('allows searching companies by name', function () {
        $this->actingAs($this->admin);

        Company::factory()->create(['name' => 'Searchable Company']);
        Company::factory()->create(['name' => 'Another Company']);

        $page = visit('/admin/companies');

        $page->type('[placeholder="Pesquisar"]', 'Searchable')
            ->press('Enter');

        $page->assertSee('Searchable Company')
            ->assertDontSee('Another Company');
    });

    it('shows company statistics and metrics', function () {
        $this->actingAs($this->admin);

        $page = visit('/admin/companies');

        // Should show total companies count
        $page->assertSee('Total de empresas')
            ->assertSee('Empresas ativas')
            ->assertSee('Novos cadastros este mês');
    });

    it('allows bulk operations on companies', function () {
        $this->actingAs($this->admin);

        $company1 = Company::factory()->create(['name' => 'Company 1']);
        $company2 = Company::factory()->create(['name' => 'Company 2']);

        $page = visit('/admin/companies');

        // Select multiple companies
        $page->check('.fi-ta-record-checkbox.fi-checkbox-input', $company1->id)
            ->check('.fi-ta-record-checkbox.fi-checkbox-input', $company2->id);

        // Perform bulk delete
        $page->click('Abrir ações')
            ->click('Excluir selecionado')
            ->assertSee('Excluir Companies selecionado')
            ->click('Excluir');

        $page->assertSee('Excluído');

        // Companies should not appear in list
        $page->assertDontSee('Company 1')
            ->assertDontSee('Company 2');
    });

    it('allows restoring deleted companies', function () {
        $this->actingAs($this->admin);

        $deletedCompany = Company::factory()->create(['name' => 'Deleted Company']);
        $deletedCompany->delete();

        $page = visit('/admin/companies');

        // Filter to show deleted companies
        $page->click('.fi-dropdown.fi-ta-filters-dropdown')
            ->select('tableFiltersForm.trashed.value', 'Somente registros excluídos')
            ->click('Aplicar filtros');

        $page->assertSee('Deleted Company');

        // Restore company
        $page->click('Restaurar')
            ->assertSee('Restaurar company')
            ->click('Restaurar');

        $page->assertSee('Restaurado');

        // Switch back to active companies
        $page->click('.fi-dropdown.fi-ta-filters-dropdown')
            ->select('tableFiltersForm.trashed.value', 'Sem registros excluídos')
            ->click('Aplicar filtros');

        $page->assertSee('Deleted Company');
    });

    it('shows company details with related data', function () {
        $this->actingAs($this->admin);

        // Create users associated with company
        $user1 = User::factory()->create(['name' => 'User 1']);
        $user2 = User::factory()->create(['name' => 'User 2']);

        $page = visit("/admin/companies/{$this->company->id}/edit");

        $page->assertSee($this->company->name)
            ->assertSee($this->company->partner_code);

        // Should show related users count or list
        $page->assertSee('Usuários associados')
            ->assertSee('Total de colaboradores');
    });

    it('validates partner code uniqueness during edit', function () {
        $this->actingAs($this->admin);

        $otherCompany = Company::factory()->create(['partner_code' => 'OTHER123']);

        $page = visit("/admin/companies/{$this->company->id}/edit");

        $page->clear('[name="partner_code"]')
            ->type('[name="partner_code"]', 'OTHER123') // Duplicate code
            ->click('Salvar')
            ->assertSee('O código do parceiro já está em uso');
    });

    it('allows exporting companies data', function () {
        $this->actingAs($this->admin);

        $page = visit('/admin/companies');

        $page->click('Exportar')
            ->assertSee('Exportar Companies')
            ->select('[name="format"]', 'xlsx')
            ->click('Exportar');

        // Should trigger download
        $page->assertSee('Exportação iniciada');
    });

    it('supports pagination for large company lists', function () {
        $this->actingAs($this->admin);

        // Create many companies
        Company::factory()->count(25)->create();

        $page = visit('/admin/companies');

        // Should show pagination controls
        $page->assertSee('Próxima')
            ->assertSee('de');

        // Navigate to next page
        $page->click('Próxima')
            ->assertUrlContains('page=2');
    });

    it('shows company activity timeline', function () {
        $this->actingAs($this->admin);

        $page = visit("/admin/companies/{$this->company->id}/edit");

        // Should show activity log
        $page->assertSee('Histórico de atividades')
            ->assertSee('Criado em')
            ->assertSee('Última atualização');
    });
});

describe('Company Registration Flow', function () {
    it('allows new company registration', function () {
        $page = visit('/company/register');

        $page->assertSee('Cadastro de Empresa')
            ->assertNoJavaScriptErrors();

        $page->type('[name="name"]', 'Nova Empresa Parceira')
            ->type('[name="email"]', 'contato@novaempresa.com')
            ->type('[name="phone"]', '(11) 98765-4321')
            ->type('[name="cnpj"]', '12.345.678/0001-90')
            ->type('[name="address"]', 'Rua das Empresas, 123')
            ->type('[name="city"]', 'São Paulo')
            ->select('[name="state"]', 'SP')
            ->type('[name="zip_code"]', '01234-567');

        $page->click('Cadastrar Empresa')
            ->assertSee('Empresa cadastrada com sucesso')
            ->assertSee('Aguarde aprovação do administrador');
    });

    it('validates company registration form', function () {
        $page = visit('/company/register');

        $page->click('Cadastrar Empresa')
            ->assertSee('O campo nome é obrigatório')
            ->assertSee('O campo e-mail é obrigatório')
            ->assertSee('O campo CNPJ é obrigatório');

        // Test invalid CNPJ
        $page->type('[name="cnpj"]', '12.345.678/0001-00') // Invalid CNPJ
            ->blur('[name="cnpj"]')
            ->assertSee('CNPJ inválido');
    });

    it('shows registration success with next steps', function () {
        $page = visit('/company/register');

        $page->type('[name="name"]', 'Empresa Teste')
            ->type('[name="email"]', 'teste@empresa.com')
            ->type('[name="phone"]', '(11) 99999-9999')
            ->type('[name="cnpj"]', '12.345.678/0001-90')
            ->click('Cadastrar Empresa');

        $page->assertSee('Cadastro realizado com sucesso')
            ->assertSee('Próximos passos')
            ->assertSee('Aguarde a aprovação')
            ->assertSee('Você receberá um e-mail');
    });
});

describe('Company Profile Management', function () {
    beforeEach(function () {
        $this->company = Company::factory()->create([
            'name' => 'Test Company',
            'partner_code' => 'TEST123',
        ]);

        $this->companyAdmin = User::factory()->create([
            'name' => 'Company Admin',
            'email' => 'admin@company.com',
        ]);
    });

    it('allows company admin to update company profile', function () {
        $this->actingAs($this->companyAdmin);

        $page = visit("/company/{$this->company->id}/profile");

        $page->assertSee('Perfil da Empresa')
            ->assertNoJavaScriptErrors();

        $page->clear('[name="name"]')
            ->type('[name="name"]', 'Updated Company Name')
            ->clear('[name="email"]')
            ->type('[name="email"]', 'newemail@company.com')
            ->click('Salvar alterações');

        $page->assertSee('Perfil atualizado com sucesso');
    });

    it('shows company statistics dashboard', function () {
        $this->actingAs($this->companyAdmin);

        $page = visit("/company/{$this->company->id}");

        $page->assertSee('Dashboard da Empresa')
            ->assertSee('Total de colaboradores')
            ->assertSee('Agendamentos este mês')
            ->assertSee('Plano atual');
    });

    it('allows managing company users', function () {
        $this->actingAs($this->companyAdmin);

        $page = visit("/company/{$this->company->id}/users");

        $page->assertSee('Colaboradores')
            ->assertSee('Adicionar colaborador');

        $page->click('Adicionar colaborador')
            ->type('[name="name"]', 'Novo Colaborador')
            ->type('[name="email"]', 'colaborador@company.com')
            ->select('[name="role"]', 'user')
            ->click('Adicionar');

        $page->assertSee('Colaborador adicionado com sucesso')
            ->assertSee('Novo Colaborador');
    });
})->group('browser', 'company-management');
