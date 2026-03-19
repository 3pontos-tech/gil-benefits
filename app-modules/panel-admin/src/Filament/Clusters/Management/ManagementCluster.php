<?php

namespace TresPontosTech\Admin\Filament\Clusters\Management;

use BackedEnum;
use Filament\Clusters\Cluster;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class ManagementCluster extends Cluster
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static ?string $slug = 'manage';

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __('panel-admin::resources.navigation_group.administration');
    }

    public static function getNavigationLabel(): string
    {
        return __('panel-admin::resources.management_cluster.navigation_label');
    }
}
