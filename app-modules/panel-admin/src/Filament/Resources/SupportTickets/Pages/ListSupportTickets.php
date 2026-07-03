<?php

declare(strict_types=1);

namespace TresPontosTech\PanelAdmin\Filament\Resources\SupportTickets\Pages;

use Filament\Resources\Pages\ListRecords;
use TresPontosTech\PanelAdmin\Filament\Resources\SupportTickets\SupportTicketResource;

class ListSupportTickets extends ListRecords
{
    protected static string $resource = SupportTicketResource::class;
}
