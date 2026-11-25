<?php

declare(strict_types=1);

namespace TresPontosTech\PanelAdmin\Filament\Clusters\Partners\Resources\Discounts\Pages;

use Filament\Resources\Pages\EditRecord;
use TresPontosTech\PanelAdmin\Filament\Clusters\Partners\Resources\Discounts\DiscountResource;

class EditDiscount extends EditRecord
{
    protected static string $resource = DiscountResource::class;
}
