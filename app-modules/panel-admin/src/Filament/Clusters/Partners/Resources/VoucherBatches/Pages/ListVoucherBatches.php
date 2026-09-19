<?php

declare(strict_types=1);

namespace TresPontosTech\PanelAdmin\Filament\Clusters\Partners\Resources\VoucherBatches\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use TresPontosTech\PanelAdmin\Filament\Clusters\Partners\Resources\VoucherBatches\VoucherBatchResource;

class ListVoucherBatches extends ListRecords
{
    protected static string $resource = VoucherBatchResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
