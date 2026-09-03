<?php

declare(strict_types=1);

namespace TresPontosTech\PanelAdmin\Filament\Clusters\Credits\Resources\CreditOrders\Pages;

use Filament\Resources\Pages\ListRecords;
use TresPontosTech\PanelAdmin\Filament\Clusters\Credits\Resources\CreditOrders\CreditOrderResource;

class ListCreditOrders extends ListRecords
{
    protected static string $resource = CreditOrderResource::class;
}
