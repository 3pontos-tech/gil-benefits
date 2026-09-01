<?php

declare(strict_types=1);

namespace TresPontosTech\PanelAdmin\Filament\Clusters\Credits\Resources\CreditGrants\Pages;

use Filament\Resources\Pages\ListRecords;
use TresPontosTech\PanelAdmin\Filament\Clusters\Credits\Resources\CreditGrants\CreditGrantResource;

class ListCreditGrants extends ListRecords
{
    protected static string $resource = CreditGrantResource::class;
}
