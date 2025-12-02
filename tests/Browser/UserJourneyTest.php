<?php

use App\Models\Users\User;
use TresPontosTech\Appointments\Models\Appointment;
use TresPontosTech\Company\Models\Company;
use TresPontosTech\Consultants\Models\Consultant;

describe('Complete User Journey', function () {
    beforeEach(function () {
        $this->company = Company::factory()->create([
            'name' => 'Journey Test Company',
            'partner_code' => 'JOURNEY123',
        ]);

        $this->consultant = Consultant::factory()->create([
            'name' => 'Dr. Journey Consultant',
            'specialty' => 'Medicina Geral',
        ]);
    });

    it('completes full user journey from registration to appointment booking', function () {
        // Step 1: User discovers the platform and registers
        $page = visit('/');

        $page->assertSee('Bem-vindo')
            ->assertNoJavaScriptErrors()
            ->click('Cadastrar como Colaborador');

        $page->waitForLocation('/partners', 5)
            ->assertSee('Cadastro de Colaborador Parceiro');

        // Step 2: Complete partner registration
        $page->type('[name="data.name"]', 'João Silva Santos')
            ->type('[name="data.rg"]', '12.345.678-9')
            ->type('[name="data.cpf"]', '111.444.777-35')
            ->type('[name="data.email"]', 'joao.journey@example.com')
            ->type('[name="data.password"]', 'SecurePass123!')
            ->type('[name="data.password_confirmation"]', 'SecurePass123!')
            ->type('[name="data.partner_code"]', 'JOURNEY123')
            ->click('Cadastrar Colaborador');

        $page->waitFor('.fi-no-title', 5)
            ->assertSee('Cadastro realizado com sucesso!')
            ->assertSee('João Silva Santos');

        // Step 3: Automatic redirect to login
        $page->waitForLocation('/app/login', 10)
            ->assertSee('Entrar');

        // Step 4: User logs in
        $page->type('[name="email"]', 'joao.journey@example.com')
            ->type('[name="password"]', 'SecurePass123!')
            ->click('Entrar');

        // Step 5: User accesses dashboard
        $page->waitForLocation('/app', 5)
            ->assertSee('Dashboard')
            ->assertSee('João Silva Santos');

        // Step 6: User navigates to appointments
        $page->click('Agendamentos')
            ->waitForLocation("/app/{$this->company->id}/appointments", 5)
            ->assertSee('Agendamentos')
            ->assertSee('Novo Agendamento');

        // Step 7: User books an appointment
        $page->click('Novo Agendamento')
            ->waitForLocation("/app/{$this->company->id}/appointments/create", 5)
            ->assertSee('Agendar Consulta');

        $futureDate = now()->addDays(7)->format('Y-m-d');
        $page->select('[name="consultant_id"]', $this->consultant->id)
            ->type('[name="appointment_date"]', $futureDate)
            ->select('[name="appointment_time"]', '14:00')
            ->type('[name="notes"]', 'Consulta de rotina - primeira vez')
            ->click('Agendar');

        $page->assertSee('Agendamento realizado com sucesso')
            ->assertSee('Dr. Journey Consultant')
            ->assertSee('14:00');

        // Step 8: User views appointment details
        $page->waitForLocation("/app/{$this->company->id}/appointments", 5)
            ->assertSee('Dr. Journey Consultant')
            ->assertSee('Agendado');

        // Step 9: User updates profile
        $page->click('Perfil')
            ->assertSee('Meu Perfil')
            ->type('[name="phone"]', '(11) 99999-9999')
            ->click('Salvar');

        $page->assertSee('Perfil atualizado com sucesso');

        // Step 10: User logs out
        $page->click('[data-testid="logout"]')
            ->waitForLocation('/app/login', 5)
            ->assertSee('Entrar');

        // Verify user was created and appointment exists
        $user = User::where('email', 'joao.journey@example.com')->first();
        expect($user)->not->toBeNull();
        expect($user->name)->toBe('João Silva Santos');

        $appointment = Appointment::where('user_id', $user->id)->first();
        expect($appointment)->not->toBeNull();
        expect($appointment->consultant_id)->toBe($this->consultant->id);
    });

    it('handles error scenarios gracefully throughout the journey', function () {
        // Test registration with invalid data
        $page = visit('/partners');

        $page->type('[name="data.name"]', 'Test User')
            ->type('[name="data.email"]', 'invalid-email')
            ->type('[name="data.partner_code"]', 'INVALID')
            ->click('Cadastrar Colaborador');

        $page->assertSee('Digite um e-mail válido')
            ->assertSee('Código do parceiro inválido');

        // Form data should be preserved
        $page->assertValue('[name="data.name"]', 'Test User');

        // Fix errors and continue
        $page->clear('[name="data.email"]')
            ->type('[name="data.email"]', 'test@example.com')
            ->clear('[name="data.partner_code"]')
            ->type('[name="data.partner_code"]', 'JOURNEY123')
            ->type('[name="data.rg"]', '12.345.678-9')
            ->type('[name="data.cpf"]', '111.444.777-35')
            ->type('[name="data.password"]', 'SecurePass123!')
            ->type('[name="data.password_confirmation"]', 'SecurePass123!')
            ->click('Cadastrar Colaborador');

        $page->waitFor('.fi-no-title', 5)
            ->assertSee('Cadastro realizado com sucesso!');
    });

    it('supports multiple user sessions simultaneously', function () {
        // Create two users
        $user1 = User::factory()->create([
            'email' => 'user1@example.com',
            'password' => bcrypt('password123'),
        ]);

        $user2 = User::factory()->create([
            'email' => 'user2@example.com',
            'password' => bcrypt('password123'),
        ]);

        // First user session
        $page1 = visit('/app/login');
        $page1->type('[name="email"]', 'user1@example.com')
            ->type('[name="password"]', 'password123')
            ->click('Entrar')
            ->waitForLocation('/app', 5)
            ->assertSee($user1->name);

        // Second user session (new browser context)
        $page2 = visit('/app/login', ['context' => 'user2']);
        $page2->type('[name="email"]', 'user2@example.com')
            ->type('[name="password"]', 'password123')
            ->click('Entrar')
            ->waitForLocation('/app', 5)
            ->assertSee($user2->name);

        // Both sessions should remain independent
        $page1->refresh()->assertSee($user1->name);
        $page2->refresh()->assertSee($user2->name);
    });

    it('maintains session state across page navigation', function () {
        $user = User::factory()->create([
            'email' => 'session@example.com',
            'password' => bcrypt('password123'),
        ]);

        $page = visit('/app/login');
        $page->type('[name="email"]', 'session@example.com')
            ->type('[name="password"]', 'password123')
            ->click('Entrar')
            ->waitForLocation('/app', 5);

        // Navigate through different pages
        $page->click('Agendamentos')
            ->waitForLocation("/app/{$this->company->id}/appointments", 5)
            ->assertSee('Agendamentos');

        $page->click('Perfil')
            ->assertSee('Meu Perfil');

        $page->visit('/app')
            ->assertSee('Dashboard');

        // User should remain logged in throughout
        $page->assertDontSee('Entrar');
    });

    it('handles network interruptions gracefully', function () {
        $page = visit('/partners');

        // Fill form partially
        $page->type('[name="data.name"]', 'Network Test User')
            ->type('[name="data.email"]', 'network@test.com');

        // Simulate network interruption
        $page->setOffline(true);

        // Try to continue filling form
        $page->type('[name="data.password"]', 'password123');

        // Restore network
        $page->setOffline(false);

        // Complete form submission
        $page->type('[name="data.rg"]', '12.345.678-9')
            ->type('[name="data.cpf"]', '111.444.777-35')
            ->type('[name="data.password_confirmation"]', 'password123')
            ->type('[name="data.partner_code"]', 'JOURNEY123')
            ->click('Cadastrar Colaborador');

        // Should handle gracefully and show appropriate message
        $page->waitFor('.fi-no', 5);
    });

    it('provides consistent user experience across different browsers', function () {
        $browsers = ['chrome', 'firefox', 'safari'];

        foreach ($browsers as $browser) {
            $page = visit('/partners', ['browser' => $browser]);

            $page->assertSee('Cadastro de Colaborador Parceiro')
                ->assertNoJavaScriptErrors()
                ->assertElementVisible('[name="data.name"]')
                ->assertElementVisible('button[type="submit"]');

            // Test basic functionality
            $page->type('[name="data.name"]', "Browser Test {$browser}")
                ->assertValue('[name="data.name"]', "Browser Test {$browser}");
        }
    });

    it('supports accessibility features throughout the journey', function () {
        $page = visit('/partners');

        // Check keyboard navigation
        $page->press('Tab') // Should focus first form field
            ->assertElementHasFocus('[name="data.name"]');

        $page->type('[name="data.name"]', 'Accessibility User')
            ->press('Tab')
            ->assertElementHasFocus('[name="data.rg"]');

        // Check ARIA labels and roles
        $page->assertElementHasAttribute('[name="data.name"]', 'aria-label')
            ->assertElementHasAttribute('button[type="submit"]', 'role', 'button');

        // Check heading structure
        $page->assertElementExists('h1')
            ->assertElementExists('[role="main"]');

        // Test screen reader announcements
        $page->click('Cadastrar Colaborador'); // Should trigger validation
        $page->assertElementExists('[role="alert"]'); // Error messages should have alert role
    });

    it('handles performance under load', function () {
        $startTime = microtime(true);

        $page = visit('/partners');
        $page->assertSee('Cadastro de Colaborador Parceiro');

        $loadTime = microtime(true) - $startTime;
        expect($loadTime)->toBeLessThan(2.0); // Should load quickly

        // Test form responsiveness
        $formStartTime = microtime(true);
        $page->type('[name="data.name"]', 'Performance Test User')
            ->type('[name="data.email"]', 'performance@test.com');
        $formTime = microtime(true) - $formStartTime;

        expect($formTime)->toBeLessThan(1.0); // Form should be responsive
    });
})->group('browser', 'user-journey');
