<?php

declare(strict_types=1);

namespace TresPontosTech\PanelAdmin\Filament\Clusters\Financial;

use BackedEnum;
use Filament\Clusters\Cluster;
use Filament\Support\Icons\Heroicon;
use TresPontosTech\PanelAdmin\Filament\Pages\Financial\RevenueDashboard;
use TresPontosTech\Permissions\Roles;
use UnitEnum;

/**
 * Cockpit financeiro: ponto único de autorização do épico FLM-41.
 *
 * O gate mora aqui e não em cada página porque receita, inadimplência e consumo
 * são o mesmo dado sensível visto de ângulos diferentes. `Admin` fica de fora de
 * propósito: hoje qualquer Admin enxerga todo o painel, e o épico existe para
 * dar ao financeiro e ao CS uma área que os demais administradores não veem.
 *
 * Um item só na sidebar, ao contrário do CreditsCluster: são cinco telas do
 * mesmo assunto, e listar as cinco lá fora afogaria o grupo Relatórios. Quem
 * entra encontra as outras na navegação interna do cluster.
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
     * O item leva direto ao dashboard, e não à página-índice do cluster.
     *
     * A página-índice existiria só para listar links que a navegação interna já
     * mostra — um clique a mais antes do primeiro número.
     */
    public static function getNavigationUrl(): string
    {
        return RevenueDashboard::getUrl();
    }
}
