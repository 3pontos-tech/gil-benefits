<?php

declare(strict_types=1);

namespace TresPontosTech\App\Filament\Resources\SharedDocuments\Pages;

use Filament\Resources\Pages\ListRecords;
use TresPontosTech\App\Filament\Resources\SharedDocuments\SharedDocumentResource;

class ListSharedDocuments extends ListRecords
{
    protected static string $resource = SharedDocumentResource::class;
}
