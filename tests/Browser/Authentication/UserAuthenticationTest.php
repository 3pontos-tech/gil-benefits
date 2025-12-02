<?php

use App\Models\Users\User;
use TresPontosTech\Company\Models\Company;

describe('User Authentication Flow', function () {
    beforeEach(function () {
        $this->company = Company::factory()->create([
            'name' => 'Test Company',
            'partner_code' => 'TEST123',
        ]);

        $this->user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
        ]);
    });

    it('allows user to login to app panel', function () {
        $page = visit('/app/login');

        $page->assertSee('Entrar')
            ->assertNoJavaScriptErrors()
            ->type('[name="email"]', 'test@example.com')
            ->type('[name="password"]', 'password123')
            ->click('Entrar');

        // Should redirect to tenant selection or dashboard
        $page->waitForLocation('/app', 5);
        $page->assertPathIs('/app');
    });

    it('shows validation errors for invalid login credentials', function () {
        $page = visit('/app/login');

        // Try empty form
        $page->click('Entrar')
            ->assertSee('O campo email é obrigatório')
            ->assertSee('O campo senha é obrigatório');

        // Try invalid email format
        $page->type('[name="email"]', 'invalid-email')
            ->type('[name="password"]', 'password123')
            ->click('Entrar')
            ->assertSee('O campo email deve ser um endereço de e-mail válido');

        // Try wrong credentials
        $page->clear('[name="email"]')
            ->type('[name="email"]', 'wrong@example.com')
            ->type('[name="password"]', 'wrongpassword')
            ->click('Entrar')
            ->assertSee('As credenciais fornecidas estão incorretas');
    });

    it('allows user to access registration page from login', function () {
        $page = visit('/app/login');

        $page->assertSee('Não tem uma conta?')
            ->click('Registrar')
            ->waitForLocation('/app/register', 5)
            ->assertPathIs('/app/register')
            ->assertSee('Criar conta');
    });

    it('allows user to logout from app panel', function () {
        $this->actingAs($this->user);

        $page = visit('/app');

        // Find and click logout button/link
        $page->click('[data-testid="logout"]')
            ->waitForLocation('/app/login', 5)
            ->assertPathIs('/app/login')
            ->assertSee('Entrar');
    });

    it('redirects unauthenticated users to login', function () {
        $page = visit('/app');

        $page->waitForLocation('/app/login', 5)
            ->assertPathIs('/app/login')
            ->assertSee('Entrar');
    });

    it('preserves intended URL after login', function () {
        // Try to access protected page
        $page = visit('/app/appointments');

        // Should redirect to login
        $page->waitForLocation('/app/login', 5)
            ->assertPathIs('/app/login');

        // Login
        $page->type('[name="email"]', 'test@example.com')
            ->type('[name="password"]', 'password123')
            ->click('Entrar');

        // Should redirect back to intended page
        $page->waitForLocation('/app/appointments', 10)
            ->assertPathContains('/appointments');
    });
});

describe('Company Panel Authentication', function () {
    beforeEach(function () {
        $this->company = Company::factory()->create([
            'name' => 'Test Company',
            'partner_code' => 'TEST123',
        ]);

        $this->companyUser = User::factory()->create([
            'name' => 'Company Admin',
            'email' => 'admin@company.com',
            'password' => bcrypt('password123'),
        ]);
    });

    it('allows company user to login to company panel', function () {
        $page = visit('/company/login');

        $page->assertSee('Entrar')
            ->assertNoJavaScriptErrors()
            ->type('[name="email"]', 'admin@company.com')
            ->type('[name="password"]', 'password123')
            ->click('Entrar');

        // Should redirect to company tenant selection or dashboard
        $page->waitForLocation('/company', 5);
        $page->assertPathIs('/company');
    });

    it('shows company-specific branding on login page', function () {
        $page = visit('/company/login');

        $page->assertSee('Painel da Empresa')
            ->assertSee('Acesso para empresas parceiras');
    });

    it('allows navigation to company registration', function () {
        $page = visit('/company/login');

        $page->click('Registrar empresa')
            ->waitForLocation('/company/register', 5)
            ->assertPathIs('/company/register')
            ->assertSee('Cadastro de Empresa');
    });
});

describe('Admin Panel Authentication', function () {
    beforeEach(function () {
        $this->admin = User::factory()->admin()->create([
            'name' => 'System Admin',
            'email' => 'admin@system.com',
            'password' => bcrypt('admin123'),
        ]);
    });

    it('allows admin to login to admin panel', function () {
        $page = visit('/admin/login');

        $page->assertSee('Entrar')
            ->assertNoJavaScriptErrors()
            ->type('[name="email"]', 'admin@system.com')
            ->type('[name="password"]', 'admin123')
            ->click('Entrar');

        $page->waitForLocation('/admin', 5)
            ->assertPathIs('/admin')
            ->assertSee('Painel de Controle');
    });

    it('restricts admin panel access to admin users only', function () {
        $regularUser = User::factory()->create([
            'email' => 'user@example.com',
            'password' => bcrypt('password123'),
        ]);

        $page = visit('/admin/login');

        $page->type('[name="email"]', 'user@example.com')
            ->type('[name="password"]', 'password123')
            ->click('Entrar')
            ->assertSee('Acesso negado')
            ->assertSee('Você não tem permissão para acessar esta área');
    });

    it('shows admin-specific navigation and features', function () {
        $this->actingAs($this->admin);

        $page = visit('/admin');

        $page->assertSee('Usuários')
            ->assertSee('Empresas')
            ->assertSee('Consultores')
            ->assertSee('Agendamentos')
            ->assertSee('Planos')
            ->assertSee('Preços');
    });
});

describe('Consultant Panel Authentication', function () {
    beforeEach(function () {
        $this->consultant = User::factory()->create([
            'name' => 'Test Consultant',
            'email' => 'consultant@example.com',
            'password' => bcrypt('password123'),
        ]);
    });

    it('allows consultant to access consultant panel', function () {
        $this->actingAs($this->consultant);

        $page = visit('/consultant');

        $page->assertSee('Painel do Consultor')
            ->assertNoJavaScriptErrors();
    });

    it('shows consultant-specific features', function () {
        $this->actingAs($this->consultant);

        $page = visit('/consultant');

        $page->assertSee('Meus Agendamentos')
            ->assertSee('Disponibilidade')
            ->assertSee('Perfil');
    });
});

describe('Multi-Panel Navigation', function () {
    beforeEach(function () {
        $this->admin = User::factory()->admin()->create([
            'email' => 'admin@example.com',
            'password' => bcrypt('password123'),
        ]);
    });

    it('allows switching between panels for authorized users', function () {
        $this->actingAs($this->admin);

        // Start at admin panel
        $page = visit('/admin');
        $page->assertSee('Painel de Controle');

        // Navigate to app panel
        $page->visit('/app')
            ->assertSee('Dashboard');

        // Navigate back to admin panel
        $page->visit('/admin')
            ->assertSee('Painel de Controle');
    });

    it('maintains separate sessions for different panels', function () {
        // Login to admin panel
        $page = visit('/admin/login');
        $page->type('[name="email"]', 'admin@example.com')
            ->type('[name="password"]', 'password123')
            ->click('Entrar')
            ->waitForLocation('/admin', 5);

        // Open new tab/window for app panel
        $appPage = visit('/app/login');
        $appPage->type('[name="email"]', 'admin@example.com')
            ->type('[name="password"]', 'password123')
            ->click('Entrar')
            ->waitForLocation('/app', 5);

        // Both panels should be accessible
        $page->visit('/admin')->assertSee('Painel de Controle');
        $appPage->visit('/app')->assertSee('Dashboard');
    });
});

describe('Password Reset Flow', function () {
    beforeEach(function () {
        $this->user = User::factory()->create([
            'email' => 'user@example.com',
        ]);
    });

    it('allows user to request password reset', function () {
        $page = visit('/app/login');

        $page->click('Esqueceu sua senha?')
            ->waitForLocation('/app/password/reset', 5)
            ->assertSee('Redefinir senha')
            ->type('[name="email"]', 'user@example.com')
            ->click('Enviar link de redefinição')
            ->assertSee('Enviamos um link de redefinição de senha para seu e-mail');
    });

    it('shows validation for invalid email in password reset', function () {
        $page = visit('/app/password/reset');

        $page->type('[name="email"]', 'nonexistent@example.com')
            ->click('Enviar link de redefinição')
            ->assertSee('Não encontramos um usuário com esse endereço de e-mail');
    });
});

describe('Session Management', function () {
    beforeEach(function () {
        $this->user = User::factory()->create([
            'email' => 'user@example.com',
            'password' => bcrypt('password123'),
        ]);
    });

    it('handles session timeout gracefully', function () {
        $this->actingAs($this->user);

        $page = visit('/app');

        // Simulate session expiration by clearing session
        $page->executeScript('
            fetch("/logout", {
                method: "POST",
                headers: {
                    "X-CSRF-TOKEN": document.querySelector("meta[name=csrf-token]").content
                }
            });
        ');

        // Try to navigate to protected page
        $page->visit('/app/appointments')
            ->waitForLocation('/app/login', 5)
            ->assertPathIs('/app/login')
            ->assertSee('Sua sessão expirou');
    });

    it('remembers user preference for "remember me"', function () {
        $page = visit('/app/login');

        $page->type('[name="email"]', 'user@example.com')
            ->type('[name="password"]', 'password123')
            ->check('[name="remember"]')
            ->click('Entrar');

        // Close browser and reopen (simulate)
        $page->refresh();

        // User should still be logged in
        $page->visit('/app')
            ->assertDontSee('Entrar');
    });
})->group('browser', 'authentication');
