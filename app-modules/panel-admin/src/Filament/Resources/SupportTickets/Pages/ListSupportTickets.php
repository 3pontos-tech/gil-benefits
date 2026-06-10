<?php

declare(strict_types=1);

namespace TresPontosTech\Admin\Filament\Resources\SupportTickets\Pages;

use Filament\Resources\Pages\ListRecords;
use TresPontosTech\Admin\Filament\Resources\SupportTickets\SupportTicketResource;

class ListSupportTickets extends ListRecords
{
    protected static string $resource = SupportTicketResource::class;
}
