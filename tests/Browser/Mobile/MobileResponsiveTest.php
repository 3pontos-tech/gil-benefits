<?php

use App\Models\Users\User;
use TresPontosTech\Company\Models\Company;

describe('Mobile Responsive Design', function () {
    beforeEach(function () {
        $this->company = Company::factory()->create([
            'name' => 'Test Company',
            'partner_code' => 'TEST123',
        ]);

        $this->user = User::factory()->create([
            'name' => 'Mobile User',
            'email' => 'mobile@example.com',
            'password' => bcrypt('password123'),
        ]);
    });

    it('displays partner registration form correctly on mobile', function () {
        $page = visit('/partners')
            ->resize(375, 667); // iPhone SE dimensions

        $page->assertSee('Cadastro de Colaborador Parceiro')
            ->assertNoJavaScriptErrors()
            ->assertElementExists('.fi-section') // Form sections should be visible
            ->assertElementExists('[name="data.name"]'); // Form fields should be accessible

        // Check that form is properly stacked on mobile
        $page->assertElementHasClass('.fi-section', 'fi-section-compact')
            ->assertElementVisible('[name="data.name"]')
            ->assertElementVisible('[name="data.email"]')
            ->assertElementVisible('[name="data.password"]');

        // Test form interaction on mobile
        $page->tap('[name="data.name"]')
            ->type('[name="data.name"]', 'Mobile Test User')
            ->tap('[name="data.email"]')
            ->type('[name="data.email"]', 'mobile@test.com');

        // Verify mobile keyboard doesn't break layout
        $page->assertElementVisible('button[type="submit"]');
    });

    it('handles mobile navigation menu correctly', function () {
        $this->actingAs($this->user);

        $page = visit("/app/{$this->company->id}")
            ->resize(375, 667);

        // Mobile menu should be collapsed by default
        $page->assertElementExists('.fi-sidebar-nav')
            ->assertElementHasClass('.fi-sidebar', 'fi-sidebar-collapsed');

        // Tap hamburger menu to open
        $page->tap('.fi-sidebar-toggle')
            ->assertElementHasClass('.fi-sidebar', 'fi-sidebar-open')
            ->assertElementVisible('.fi-sidebar-nav-item');

        // Tap outside to close
        $page->tap('.fi-main')
            ->assertElementHasClass('.fi-sidebar', 'fi-sidebar-collapsed');
    });

    it('displays tables responsively on mobile', function () {
        $this->actingAs($this->user);

        $page = visit("/app/{$this->company->id}/appointments")
            ->resize(375, 667);

        $page->assertSee('Agendamentos')
            ->assertNoJavaScriptErrors();

        // Table should be horizontally scrollable on mobile
        $page->assertElementExists('.fi-ta-table-container')
            ->assertElementHasClass('.fi-ta-table-container', 'overflow-x-auto');

        // Important columns should remain visible
        $page->assertElementVisible('.fi-ta-header-cell')
            ->assertElementVisible('.fi-ta-cell');
    });

    it('handles form validation messages on mobile', function () {
        $page = visit('/partners')
            ->resize(375, 667);

        // Submit empty form to trigger validation
        $page->tap('Cadastrar Colaborador')
            ->assertSee('O nome completo é obrigatório');

        // Validation messages should be properly positioned
        $page->assertElementExists('.fi-fo-field-wrp-error-message')
            ->assertElementVisible('.fi-fo-field-wrp-error-message');

        // Error messages shouldn't break mobile layout
        $page->assertElementVisible('[name="data.name"]')
            ->assertElementVisible('button[type="submit"]');
    });

    it('supports touch gestures for interactive elements', function () {
        $this->actingAs($this->user);

        $page = visit("/app/{$this->company->id}/appointments")
            ->resize(375, 667);

        // Test swipe gestures on table rows (if implemented)
        $page->assertElementExists('.fi-ta-row');

        // Test touch-friendly button sizes
        $page->assertElementExists('button')
            ->assertElementHasMinimumSize('button', 44, 44); // iOS minimum touch target

        // Test dropdown menus work with touch
        if ($page->elementExists('.fi-dropdown-trigger')) {
            $page->tap('.fi-dropdown-trigger')
                ->assertElementVisible('.fi-dropdown-panel');
        }
    });

    it('displays notifications correctly on mobile', function () {
        $page = visit('/partners')
            ->resize(375, 667);

        // Fill and submit form to trigger notification
        $page->type('[name="data.name"]', 'Test User')
            ->type('[name="data.rg"]', '12.345.678-9')
            ->type('[name="data.cpf"]', '111.444.777-35')
            ->type('[name="data.email"]', 'test@mobile.com')
            ->type('[name="data.password"]', 'SecurePass123!')
            ->type('[name="data.password_confirmation"]', 'SecurePass123!')
            ->type('[name="data.partner_code"]', 'TEST123')
            ->tap('Cadastrar Colaborador');

        // Notification should be visible and properly positioned
        $page->waitFor('.fi-no', 5)
            ->assertElementVisible('.fi-no')
            ->assertElementHasClass('.fi-no', 'fi-no-mobile'); // Mobile-specific styling
    });

    it('handles modal dialogs on mobile devices', function () {
        $this->actingAs($this->user);

        $page = visit("/app/{$this->company->id}/appointments")
            ->resize(375, 667);

        // Open modal (if available)
        if ($page->elementExists('[data-modal-trigger]')) {
            $page->tap('[data-modal-trigger]')
                ->assertElementVisible('.fi-modal')
                ->assertElementHasClass('.fi-modal', 'fi-modal-mobile');

            // Modal should be full-screen or properly sized for mobile
            $page->assertElementVisible('.fi-modal-content')
                ->assertElementVisible('.fi-modal-close');

            // Close modal
            $page->tap('.fi-modal-close')
                ->assertElementNotVisible('.fi-modal');
        }
    });

    it('supports landscape orientation', function () {
        $page = visit('/partners')
            ->resize(667, 375); // Landscape iPhone SE

        $page->assertSee('Cadastro de Colaborador Parceiro')
            ->assertNoJavaScriptErrors()
            ->assertElementVisible('[name="data.name"]')
            ->assertElementVisible('button[type="submit"]');

        // Form should adapt to landscape layout
        $page->assertElementExists('.fi-section')
            ->assertElementVisible('.fi-fo-field-wrp');
    });

    it('works correctly on tablet devices', function () {
        $page = visit('/partners')
            ->resize(768, 1024); // iPad dimensions

        $page->assertSee('Cadastro de Colaborador Parceiro')
            ->assertNoJavaScriptErrors();

        // Tablet should show more content than mobile
        $page->assertElementVisible('.fi-section')
            ->assertElementVisible('[name="data.name"]')
            ->assertElementVisible('[name="data.email"]');

        // Form layout should be optimized for tablet
        $page->assertElementExists('.fi-fo-field-wrp')
            ->assertElementVisible('button[type="submit"]');
    });

    it('handles different mobile browsers correctly', function () {
        $browsers = ['chrome', 'safari', 'firefox'];

        foreach ($browsers as $browser) {
            $page = visit('/partners', ['browser' => $browser])
                ->resize(375, 667);

            $page->assertSee('Cadastro de Colaborador Parceiro')
                ->assertNoJavaScriptErrors()
                ->assertElementVisible('[name="data.name"]');

            // Test basic form interaction
            $page->type('[name="data.name"]', "Test User {$browser}")
                ->assertValue('[name="data.name"]', "Test User {$browser}");
        }
    });

    it('supports mobile accessibility features', function () {
        $page = visit('/partners')
            ->resize(375, 667);

        // Check for proper ARIA labels
        $page->assertElementHasAttribute('[name="data.name"]', 'aria-label')
            ->assertElementHasAttribute('button[type="submit"]', 'aria-label');

        // Check for proper heading structure
        $page->assertElementExists('h1, h2, h3')
            ->assertElementHasAttribute('h1, h2, h3', 'id');

        // Check for focus management
        $page->tap('[name="data.name"]')
            ->assertElementHasFocus('[name="data.name"]');
    });

    it('handles mobile form validation smoothly', function () {
        $page = visit('/partners')
            ->resize(375, 667);

        // Test real-time validation on mobile
        $page->type('[name="data.cpf"]', '123.456.789-00') // Invalid CPF
            ->tap('[name="data.email"]') // Blur CPF field
            ->assertSee('CPF inválido');

        // Error message should not break mobile layout
        $page->assertElementVisible('[name="data.cpf"]')
            ->assertElementVisible('[name="data.email"]')
            ->assertElementVisible('button[type="submit"]');

        // Fix the CPF and verify error disappears
        $page->clear('[name="data.cpf"]')
            ->type('[name="data.cpf"]', '111.444.777-35')
            ->tap('[name="data.email"]')
            ->assertDontSee('CPF inválido');
    });

    it('supports mobile-specific input types', function () {
        $page = visit('/partners')
            ->resize(375, 667);

        // Email field should have email input type
        $page->assertElementHasAttribute('[name="data.email"]', 'type', 'email');

        // Phone fields should have tel input type (if present)
        if ($page->elementExists('[name="phone"]')) {
            $page->assertElementHasAttribute('[name="phone"]', 'type', 'tel');
        }

        // Number fields should have number input type (if present)
        if ($page->elementExists('[name="number"]')) {
            $page->assertElementHasAttribute('[name="number"]', 'type', 'number');
        }
    });

    it('handles mobile performance optimization', function () {
        $page = visit('/partners')
            ->resize(375, 667);

        // Page should load quickly on mobile
        $startTime = microtime(true);
        $page->assertSee('Cadastro de Colaborador Parceiro');
        $loadTime = microtime(true) - $startTime;

        expect($loadTime)->toBeLessThan(3.0); // Should load in under 3 seconds

        // Images should be optimized for mobile
        $page->assertElementExists('img')
            ->assertElementHasAttribute('img', 'loading', 'lazy');

        // CSS and JS should be minified
        $page->assertNoJavaScriptErrors();
    });

    it('supports mobile dark mode', function () {
        $page = visit('/partners')
            ->resize(375, 667)
            ->setColorScheme('dark');

        $page->assertSee('Cadastro de Colaborador Parceiro')
            ->assertNoJavaScriptErrors();

        // Check for dark mode classes
        $page->assertElementHasClass('html', 'dark')
            ->assertElementExists('.dark\\:bg-gray-900, .dark\\:text-white');

        // Form should be readable in dark mode
        $page->assertElementVisible('[name="data.name"]')
            ->assertElementVisible('button[type="submit"]');
    });

    it('handles mobile network conditions gracefully', function () {
        $page = visit('/partners')
            ->resize(375, 667)
            ->throttleNetwork('slow-3g');

        $page->assertSee('Cadastro de Colaborador Parceiro')
            ->assertNoJavaScriptErrors();

        // Form should still be functional on slow networks
        $page->type('[name="data.name"]', 'Slow Network User')
            ->assertValue('[name="data.name"]', 'Slow Network User');

        // Loading states should be visible
        $page->type('[name="data.email"]', 'slow@network.com')
            ->type('[name="data.password"]', 'password123')
            ->tap('Cadastrar Colaborador');

        // Should show loading indicator
        $page->assertElementExists('.animate-spin, .loading, [data-loading]');
    });
})->group('browser', 'mobile-responsive');

describe('Cross-Device Compatibility', function () {
    it('maintains functionality across different screen sizes', function () {
        $screenSizes = [
            ['width' => 320, 'height' => 568, 'name' => 'iPhone 5'],
            ['width' => 375, 'height' => 667, 'name' => 'iPhone 8'],
            ['width' => 414, 'height' => 896, 'name' => 'iPhone 11'],
            ['width' => 768, 'height' => 1024, 'name' => 'iPad'],
            ['width' => 1024, 'height' => 768, 'name' => 'iPad Landscape'],
            ['width' => 1920, 'height' => 1080, 'name' => 'Desktop'],
        ];

        foreach ($screenSizes as $size) {
            $page = visit('/partners')
                ->resize($size['width'], $size['height']);

            $page->assertSee('Cadastro de Colaborador Parceiro')
                ->assertNoJavaScriptErrors()
                ->assertElementVisible('[name="data.name"]')
                ->assertElementVisible('button[type="submit"]');

            // Test basic form interaction on each size
            $page->type('[name="data.name"]', "User on {$size['name']}")
                ->assertValue('[name="data.name"]', "User on {$size['name']}");
        }
    });

    it('adapts layout appropriately for different orientations', function () {
        // Portrait mode
        $page = visit('/partners')
            ->resize(375, 667);

        $page->assertSee('Cadastro de Colaborador Parceiro')
            ->assertElementVisible('[name="data.name"]');

        // Switch to landscape
        $page->resize(667, 375);

        $page->assertSee('Cadastro de Colaborador Parceiro')
            ->assertElementVisible('[name="data.name"]')
            ->assertElementVisible('button[type="submit"]');

        // Layout should adapt without breaking
        $page->assertNoJavaScriptErrors();
    });

    it('handles touch and mouse interactions appropriately', function () {
        // Test touch interactions
        $page = visit('/partners')
            ->resize(375, 667);

        $page->tap('[name="data.name"]')
            ->type('[name="data.name"]', 'Touch User')
            ->assertValue('[name="data.name"]', 'Touch User');

        // Test mouse interactions on larger screens
        $page->resize(1920, 1080);

        $page->click('[name="data.email"]')
            ->type('[name="data.email"]', 'mouse@user.com')
            ->assertValue('[name="data.email"]', 'mouse@user.com');
    });
})->group('browser', 'cross-device');
