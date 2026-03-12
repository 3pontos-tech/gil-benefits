<?php

namespace TresPontosTech\Admin\Filament\Clusters\Partners;

use BackedEnum;
use Filament\Clusters\Cluster;
use Filament\Support\Icons\Heroicon;

class PartnersCluster extends Cluster
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSquares2x2;

    public static function canAccess(): bool
    {
        return false;
    }
}
