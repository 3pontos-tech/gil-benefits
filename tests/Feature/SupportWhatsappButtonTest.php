<?php

declare(strict_types=1);

use App\Models\Users\User;
use TresPontosTech\Billing\Core\Enums\CompanyPlanStatusEnum;
use TresPontosTech\Billing\Core\Models\CompanyPlan;
use TresPontosTech\Company\Models\Company;
use TresPontosTech\PanelApp\Filament\Pages\EditUserProfile;
use TresPontosTech\Permissions\Roles;

beforeEach(function (): void {
    config(['services.whatsapp.support_phone' => '5511999999999']);
});

it('opens the support WhatsApp with the origin of each site page', function (string $route, string $area, string $origin): void {
    $this->get(route($route))
        ->assertOk()
        ->assertSee('href="https://wa.me/5511999999999?text=' . rawurlencode('Olá! Gostaria de falar com o suporte. ' . $origin) . '"', false)
        ->assertSee('data-support-whatsapp="' . $area . '"', false)
        ->assertSee('target="_blank"', false)
        ->assertSee('aria-label="Falar com o suporte pelo WhatsApp (abre em nova aba)"', false);
})->with([
    'home' => ['site.home', 'home', 'Estou no site do Flamma, na página inicial.'],
    'companies' => ['site.companies', 'empresas', 'Estou no site do Flamma, na página Para Empresas.'],
    'collaborator' => ['site.collaborator', 'colaboradores', 'Estou no site do Flamma, na página Para Você.'],
    'privacy' => ['site.privacy', 'privacidade', 'Estou no site do Flamma, na página de Privacidade.'],
]);

it('shows the button on the auth pages of the customer panels', function (string $route, string $area): void {
    $this->get(route($route))
        ->assertOk()
        ->assertSee('data-support-whatsapp="' . $area . '"', false);
})->with([
    'employee login' => ['filament.app.auth.login', 'painel_colaborador'],
    'employee signup' => ['filament.app.auth.register', 'painel_colaborador'],
    'employee password reset' => ['filament.app.auth.password-reset.request', 'painel_colaborador'],
    'company login' => ['filament.company.auth.login', 'painel_empresa'],
]);

it('keeps the password reset token and e-mail out of the WhatsApp referrer', function (): void {
    $user = User::factory()->create();
    filament()->setCurrentPanel('app');

    $this->get(filament()->getResetPasswordUrl('reset-token', $user))
        ->assertOk()
        ->assertSee('data-support-whatsapp="painel_colaborador"', false)
        ->assertSee('rel="noopener noreferrer"', false);
});

it('opens the support WhatsApp with the employee panel origin', function (): void {
    actingAsEmployee();

    $this->get(EditUserProfile::getUrl(panel: 'app'))
        ->assertOk()
        ->assertSee('href="https://wa.me/5511999999999?text=' . rawurlencode('Olá! Gostaria de falar com o suporte. Estou no painel do colaborador do Flamma.') . '"', false);
});

it('opens the support WhatsApp with the company panel origin and loads posthog-js once', function (): void {
    config(['services.posthog.key' => 'phc_test']);
    $owner = User::factory()->companyOwner()->create();
    $company = Company::factory()->recycle($owner)->create();
    $company->employees()->attach($owner->getKey());
    CompanyPlan::factory()->create([
        'company_id' => $company->getKey(),
        'status' => CompanyPlanStatusEnum::Active->value,
        'starts_at' => now()->subDay(),
    ]);

    $response = $this->actingAs($owner)->get(route('filament.company.pages.dashboard', ['tenant' => $company->slug]));

    $response->assertOk()
        ->assertSee('href="https://wa.me/5511999999999?text=' . rawurlencode('Olá! Gostaria de falar com o suporte. Estou no painel da empresa do Flamma.') . '"', false);
    expect(substr_count($response->getContent(), 'posthog.init('))->toBe(1);
});

it('hides the button and posthog-js from an admin inside the company panel', function (): void {
    config(['services.posthog.key' => 'phc_test']);
    $owner = User::factory()->companyOwner()->create();
    $company = Company::factory()->recycle($owner)->create();
    $company->employees()->attach($owner->getKey());
    CompanyPlan::factory()->create([
        'company_id' => $company->getKey(),
        'status' => CompanyPlanStatusEnum::Active->value,
        'starts_at' => now()->subDay(),
    ]);
    actingAsAdmin();

    $this->get(route('filament.company.pages.dashboard', ['tenant' => $company->slug]))
        ->assertOk()
        ->assertDontSee('data-support-whatsapp', false)
        ->assertDontSee('posthog.init(', false);
});

it('captures the click in PostHog with the area of the button', function (): void {
    $this->get(route('site.home'))
        ->assertOk()
        ->assertSee("window.posthog.capture('suporte_whatsapp_clicado', {", false)
        ->assertSee('area: link.dataset.supportWhatsapp,', false)
        ->assertSee("button_location: 'floating_bottom_left',", false);
});

it('keeps only the digits of the configured support phone', function (): void {
    config(['services.whatsapp.support_phone' => '+55 (11) 99999-9999']);

    $this->get(route('site.home'))
        ->assertOk()
        ->assertSee('href="https://wa.me/5511999999999?text=', false);
});

it('hides the button without a support phone', function (?string $phone, string $route): void {
    config(['services.whatsapp.support_phone' => $phone]);

    $this->get(route($route))
        ->assertOk()
        ->assertDontSee('data-support-whatsapp', false);
})->with(['null' => null, 'empty' => ''])->with([
    'site' => 'site.home',
    'employee panel' => 'filament.app.auth.login',
    'company panel' => 'filament.company.auth.login',
]);

it('hides the button from the internal team', function (Roles $role): void {
    $this->actingAs(User::factory()->create()->assignRole($role))
        ->get(route('site.home'))
        ->assertOk()
        ->assertDontSee('data-support-whatsapp', false);
})->with([Roles::SuperAdmin, Roles::Admin, Roles::Consultant, Roles::Financial, Roles::CustomerSuccess]);

it('keeps the commercial WhatsApp of the site CTAs apart from the support one', function (): void {
    config(['services.whatsapp.url' => 'https://wa.me/5511888888888?text=Flamma']);

    $this->get(route('site.home'))
        ->assertOk()
        ->assertSee('https://wa.me/5511888888888?text=Flamma', false)
        ->assertSee('https://wa.me/5511999999999?text=', false);
});

it('accepts the area as its string value', function (): void {
    $this->blade('<x-support-whatsapp area="privacidade" />')
        ->assertSee('data-support-whatsapp="privacidade"', false);
});
