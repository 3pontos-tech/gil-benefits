<?php

declare(strict_types=1);

use App\Filament\FilamentPanel;
use TresPontosTech\PanelApp\Filament\Pages\EditUserProfile;
use TresPontosTech\PanelApp\Support\NavigationGroupDescriptions;

use function Pest\Laravel\get;

it('groups the navigation and describes each group, as the layout asks', function (): void {
    actingAsSubscribedEmployee();
    filament()->setTenant(null);

    $page = get(EditUserProfile::getUrl())->assertSuccessful();

    foreach (['platform', 'appointments', 'support'] as $group) {
        $page
            ->assertSee(__(sprintf('panel-app::navigation.groups.%s.label', $group)))
            ->assertSee(__(sprintf('panel-app::navigation.groups.%s.description', $group)));
    }
});

it('renders the navigation items under their groups', function (): void {
    actingAsSubscribedEmployee();
    filament()->setTenant(null);

    get(EditUserProfile::getUrl())
        ->assertSuccessful()
        ->assertSee(__('all.my_profile'))
        ->assertSee(__('panel-app::pages.dashboard.navigation_label'))
        ->assertSee('Materiais')
        ->assertSee('fi-sidebar-group-description', escape: false);
});

it('marks the manually registered profile item as active on the profile page', function (): void {
    actingAsSubscribedEmployee();
    filament()->setTenant(null);

    $html = get(EditUserProfile::getUrl())->assertSuccessful()->getContent();

    /**
     * Classes do <li> da sidebar que contém o rótulo informado. O item de perfil
     * é um NavigationItem manual: sem isActiveWhen ele nasce com isActive = null
     * e nunca recebe fi-active, ao contrário dos resources e pages.
     *
     * A busca começa na sidebar porque "Meu Perfil" também é o <title> da página,
     * e o token é casado com `(?![-\w])` para não confundir o <li> com os
     * `fi-sidebar-item-icon` / `-label` que ficam mais perto do rótulo.
     */
    $itemClasses = function (string $label) use ($html): string {
        $sidebarAt = mb_strpos($html, 'fi-sidebar-nav');
        expect($sidebarAt)->not->toBeFalse();

        $sidebar = mb_substr($html, $sidebarAt);
        $at = mb_strpos($sidebar, $label);
        expect($at)->not->toBeFalse();

        preg_match_all(
            '/class="([^"]*fi-sidebar-item(?![-\w])[^"]*)"/',
            mb_substr($sidebar, 0, $at),
            $m,
        );

        return end($m[1]) ?: '';
    };

    expect($itemClasses(__('all.my_profile')))->toContain('fi-active')
        // Discrimina: o item do dashboard não pode estar ativo aqui.
        ->and($itemClasses(__('panel-app::pages.dashboard.navigation_label')))->not->toContain('fi-active');
});

it('omits the chat bot item, which has no implementation', function (): void {
    actingAsSubscribedEmployee();
    filament()->setTenant(null);

    get(EditUserProfile::getUrl())
        ->assertSuccessful()
        ->assertDontSee('Chat bot');
});

it('keeps the sidebar search out, since global search stays disabled', function (): void {
    actingAsSubscribedEmployee();

    expect(filament()->getCurrentOrDefaultPanel()?->getGlobalSearchProvider())->toBeNull();

    get(EditUserProfile::getUrl())
        ->assertSuccessful()
        ->assertDontSee('fi-global-search', escape: false);
});

describe('group descriptions', function (): void {
    it('resolves the description for a known group label', function (): void {
        actingAsSubscribedEmployee();

        expect(NavigationGroupDescriptions::for(__('panel-app::navigation.groups.support.label')))
            ->toBe(__('panel-app::navigation.groups.support.description'));
    });

    it('returns null for an unknown label', function (): void {
        actingAsSubscribedEmployee();

        expect(NavigationGroupDescriptions::for('Grupo Inexistente'))->toBeNull()
            ->and(NavigationGroupDescriptions::for(null))->toBeNull();
    });

    // A view sobrescrita é global. "Agendamentos" existe como grupo no painel
    // admin também, então o recorte por painel é o que evita herdar a frase.
    it('does not leak the description into other panels', function (): void {
        actingAsAdmin();

        expect(filament()->getCurrentOrDefaultPanel()?->getId())->toBe(FilamentPanel::Admin->value)
            ->and(NavigationGroupDescriptions::for(__('panel-app::navigation.groups.appointments.label')))
            ->toBeNull();
    });
});
