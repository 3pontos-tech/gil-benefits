<?php

declare(strict_types=1);

namespace TresPontosTech\PanelApp\Filament\Resources\SharedDocuments\Pages;

use Filament\Resources\Pages\ListRecords;
use TresPontosTech\PanelApp\Filament\Resources\SharedDocuments\SharedDocumentResource;
use TresPontosTech\PanelApp\Filament\Resources\SharedDocuments\Widgets\MyMaterialsWidget;

class ListSharedDocuments extends ListRecords
{
    protected static string $resource = SharedDocumentResource::class;

    /**
     * As duas seções carregam seus próprios títulos ("Meus materiais" e
     * "Compartilhados comigo"); sem heading, o Filament nem renderiza o
     * cabeçalho da página. O getTitle segue preenchido para a aba do browser.
     */
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
