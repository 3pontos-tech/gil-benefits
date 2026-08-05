<?php

declare(strict_types=1);

use App\Models\Users\Detail;
use App\Models\Users\User;
use TresPontosTech\PanelApp\Filament\Pages\EditUserProfile;
use TresPontosTech\User\Enums\LifeMoment;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\get;
use function Pest\Livewire\livewire;

beforeEach(function (): void {
    $this->employee = actingAsSubscribedEmployee();
});

it('renders the profile with the welcome heading, the avatar section and both tabs', function (): void {
    livewire(EditUserProfile::class)
        ->assertSuccessful()
        ->assertSee($this->employee->name)
        ->assertSee('Gerencie suas informações pessoais, segurança e preferências.')
        ->assertSee('Foto de perfil')
        ->assertSee('Informações pessoais')
        ->assertSee('Informações financeiras');
});

it('states the accepted formats and the size limit on the avatar upload', function (): void {
    $avatar = livewire(EditUserProfile::class)
        ->assertSuccessful()
        ->assertSee('PNG, JPG ou WEBP até 5MB. Recomendado: imagem quadrada.')
        ->instance()
        ->getSchema('form')
        ?->getFlatFields(withHidden: true)['avatar'] ?? null;

    expect($avatar)->not->toBeNull()
        ->and($avatar->getMaxSize())->toBe(5120)
        ->and($avatar->getAcceptedFileTypes())->toBe(['image/jpeg', 'image/png', 'image/webp']);
});

it('wraps both tabs content in sections, so no heading sits flush against the edge', function (): void {
    filament()->setTenant(null);

    $html = get(EditUserProfile::getUrl())->assertSuccessful()->getContent();

    // Cada bloco das duas abas precisa vir como cabeçalho de Section. Sem isso o
    // texto fica solto, sem o padding do card e sem respiro após a barra de abas.
    foreach ([
        __('panel-app::profile.personal.heading'),
        __('panel-app::profile.security.heading'),
        __('panel-app::profile.financial.heading'),
    ] as $heading) {
        expect($html)->toMatch(
            '/fi-section-header-heading[^>]*>\s*' . preg_quote((string) $heading, '/') . '/u'
        );
    }
});

it('renders the account summary and the support cards beside the form', function (): void {
    livewire(EditUserProfile::class)
        ->assertSuccessful()
        ->assertSee('Sua conta')
        ->assertSee('Resumo rápido do seu perfil')
        ->assertSee('Precisa de ajuda?')
        ->assertSee('Falar com suporte');
});

it('renders over HTTP on the real route, which resolves no tenant', function (): void {
    // A rota do perfil é `app/profile`, sem o segmento {tenant}. O setTenant() dos
    // helpers de teste persiste no processo e sobrevive à requisição, mascarando a
    // condição real — por isso o tenant é zerado aqui de propósito. Sem isso, o
    // teste passa mesmo com as URLs tenant-scoped quebradas.
    filament()->setTenant(null);

    get(EditUserProfile::getUrl())
        ->assertSuccessful()
        ->assertSee('Precisa de ajuda?')
        ->assertSee('Falar com suporte')
        ->assertSee('support-tickets/create');
});

it('builds the tenant-scoped sidebar links on the tenantless profile route', function (): void {
    filament()->setTenant(null);

    // Os itens da sidebar apontam para rotas `app/{tenant}/...`; montá-las sem
    // tenant em escopo lançava UrlGenerationException ao renderizar a página.
    get(EditUserProfile::getUrl())
        ->assertSuccessful()
        ->assertSee('my-credits')
        ->assertSee('appointments')
        ->assertSee('shared-documents');
});

it('hides the support card when the user has no company to open a ticket for', function (): void {
    filament()->setTenant(null);
    $this->employee->companies()->detach();

    livewire(EditUserProfile::class)
        ->assertSuccessful()
        ->assertDontSee('Falar com suporte');
});

it('reports the account summary from data the application actually stores', function (): void {
    $this->employee->forceFill(['email_verified_at' => null])->saveQuietly();

    livewire(EditUserProfile::class)
        ->assertSuccessful()
        ->assertSee('Não verificado')
        ->assertSee('Não informado')
        ->assertSee('Incompletas');
});

it('reports verified email and provided phone once both are present', function (): void {
    $this->employee->forceFill(['email_verified_at' => now()])->saveQuietly();
    Detail::query()->create([
        'user_id' => $this->employee->getKey(),
        'tax_id' => '97692325057',
        'phone_number' => '+5511999999999',
    ]);

    livewire(EditUserProfile::class)
        ->assertSuccessful()
        ->assertSee('Verificado')
        ->assertSee('Preenchido')
        ->assertSee('Atualizadas');
});

it('saves the personal information and the anamnese in a single submit', function (): void {
    Detail::query()->create([
        'user_id' => $this->employee->getKey(),
        'tax_id' => '97692325057',
    ]);

    livewire(EditUserProfile::class)
        ->fillForm([
            'name' => 'Nome Atualizado',
            'phone_number' => '+5511988887777',
            'life_moment' => LifeMoment::cases()[0]->value,
            'main_motivation' => 'Sair das dívidas',
            'money_relationship' => 'Ainda me organizando',
            'plans_monthly_expenses' => 'Planejo todo mês',
            'tried_financial_strategies' => 'Já tentei planilhas',
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    assertDatabaseHas(User::class, [
        'id' => $this->employee->getKey(),
        'name' => 'Nome Atualizado',
    ]);

    assertDatabaseHas('user_anamneses', [
        'user_id' => $this->employee->getKey(),
        'main_motivation' => 'Sair das dívidas',
        'tried_financial_strategies' => 'Já tentei planilhas',
    ]);
});

it('still requires the current password to change the password', function (): void {
    Detail::query()->create([
        'user_id' => $this->employee->getKey(),
        'tax_id' => '97692325057',
    ]);

    livewire(EditUserProfile::class)
        ->fillForm([
            'life_moment' => LifeMoment::cases()[0]->value,
            'main_motivation' => 'Sair das dívidas',
            'money_relationship' => 'Ainda me organizando',
            'plans_monthly_expenses' => 'Planejo todo mês',
            'tried_financial_strategies' => 'Já tentei planilhas',
            'password' => 'nova-senha-forte',
            'passwordConfirmation' => 'nova-senha-forte',
        ])
        ->call('save')
        ->assertHasFormErrors(['currentPassword']);
});
