<?php

declare(strict_types=1);

namespace TresPontosTech\PanelApp\Filament\Resources\SharedDocuments\Pages;

use Filament\Resources\Pages\ListRecords;
use TresPontosTech\PanelApp\Filament\Resources\SharedDocuments\SharedDocumentResource;
use TresPontosTech\PanelApp\Filament\Resources\SharedDocuments\Widgets\MyMaterialsWidget;

class ListSharedDocuments extends ListRecords
{
    protected static string $resource = SharedDocumentResource::class;

    public function getHeading(): string
    {
        return '';
    }

    protected function getHeaderWidgets(): array
    {
        return [
            MyMaterialsWidget::class,
        ];
    }
}
