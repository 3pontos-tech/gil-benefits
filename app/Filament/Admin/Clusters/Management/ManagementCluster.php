<?php

namespace App\Filament\Admin\Clusters\Management;

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
        return 'Administration';
    }

    public static function getNavigationLabel(): string
    {
        return 'Users Management';
    }
}
