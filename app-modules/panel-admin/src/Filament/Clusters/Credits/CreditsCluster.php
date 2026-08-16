<?php

declare(strict_types=1);

namespace TresPontosTech\PanelAdmin\Filament\Clusters\Credits;

use BackedEnum;
use Filament\Clusters\Cluster;
use Filament\Navigation\NavigationItem;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class CreditsCluster extends Cluster
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTicket;

    protected static ?string $slug = 'credits';

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __('panel-admin::resources.navigation_group.credits');
    }

    public static function getNavigationLabel(): string
    {
        return __('panel-admin::resources.credits_cluster.navigation_label');
    }

    /**
     * Um item de sidebar por componente, em vez do único item do cluster.
     *
     * O padrão do Filament esconde os componentes atrás do cluster, e a doc trata
     * isso como característica, não como opção. Aqui o agrupamento importa mas a
     * descoberta importa mais: ninguém deve precisar entrar para saber o que tem.
     *
     * As URLs, abas de topo e breadcrumbs do cluster continuam valendo.
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
