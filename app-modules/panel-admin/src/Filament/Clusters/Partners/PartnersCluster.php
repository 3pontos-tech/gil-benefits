<?php

declare(strict_types=1);

namespace TresPontosTech\PanelAdmin\Filament\Clusters\Partners;

use BackedEnum;
use Filament\Clusters\Cluster;
use Filament\Navigation\NavigationItem;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class PartnersCluster extends Cluster
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSquares2x2;

    protected static ?string $slug = 'partners';

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __('panel-admin::resources.navigation_group.partners');
    }

    public static function getNavigationLabel(): string
    {
        return __('panel-admin::resources.partners_cluster.navigation_label');
    }

    /**
     * @return array<NavigationItem>
     */
    public static function getNavigationItems(): array
    {
        return collect(static::getClusteredComponents())
            ->flatMap(fn (string $component): array => $component::getNavigationItems())
            ->all();
    }
}
