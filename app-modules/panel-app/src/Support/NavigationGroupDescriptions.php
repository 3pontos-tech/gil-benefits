<?php

declare(strict_types=1);

namespace TresPontosTech\PanelApp\Support;

use App\Filament\FilamentPanel;
use Filament\Facades\Filament;

/**
 * Resolve a frase descritiva de um grupo da sidebar.
 *
 * O `NavigationGroup` do Filament não tem campo de descrição e a view que
 * renderiza o grupo só recebe o rótulo — não o objeto do grupo. Por isso a busca
 * é pelo rótulo já traduzido, comparando com os mesmos `__()` que alimentam o
 * `navigationGroups()` do painel, o que mantém os dois lados no mesmo idioma.
 */
final class NavigationGroupDescriptions
{
    /** Chaves em `panel-app::navigation.groups`, na ordem da sidebar. */
    private const array GROUPS = ['platform', 'appointments', 'support'];

    public static function for(?string $label): ?string
    {
        // A view sobrescrita é global; sem este recorte os grupos dos painéis
        // admin/company/consultant herdariam descrição por coincidência de nome.
        if (blank($label) || Filament::getCurrentOrDefaultPanel()?->getId() !== FilamentPanel::User->value) {
            return null;
        }

        foreach (self::GROUPS as $group) {
            if ((string) __(sprintf('panel-app::navigation.groups.%s.label', $group)) === $label) {
                return (string) __(sprintf('panel-app::navigation.groups.%s.description', $group));
            }
        }

        return null;
    }
}
