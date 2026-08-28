<?php

declare(strict_types=1);

namespace TresPontosTech\PanelAdmin\Filament\Clusters\Financial;

use BackedEnum;
use Filament\Clusters\Cluster;
use Filament\Navigation\NavigationItem;
use Filament\Support\Icons\Heroicon;
use TresPontosTech\Permissions\Roles;
use UnitEnum;

/**
 * Cockpit financeiro: ponto único de autorização do épico FLM-41.
 *
 * O gate mora aqui e não em cada página porque receita, inadimplência e consumo
 * são o mesmo dado sensível visto de ângulos diferentes. `Admin` fica de fora de
 * propósito: hoje qualquer Admin enxerga todo o painel, e o épico existe para
 * dar ao financeiro e ao CS uma área que os demais administradores não veem.
 */
class FinancialCluster extends Cluster
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    protected static ?string $slug = 'financial';

    public static function canAccess(): bool
    {
        return auth()->user()?->hasAnyRole(Roles::financialValues()) ?? false;
    }

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __('panel-admin::resources.navigation_group.reports');
    }

    public static function getNavigationLabel(): string
    {
        return __('panel-admin::resources.financial_cluster.navigation_label');
    }

    /**
     * Um item de sidebar por página, seguindo a mesma escolha do CreditsCluster:
     * o agrupamento importa, mas a descoberta importa mais.
     *
     * @return array<NavigationItem>
     */
    public static function getNavigationItems(): array
    {
        return collect(static::getClusteredComponents())
            ->flatMap(fn (string $component): array => $component::getNavigationItems())
            ->all();
    }
}
