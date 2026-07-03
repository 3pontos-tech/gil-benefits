<?php

declare(strict_types=1);

namespace TresPontosTech\PanelCompany\Filament\Resources\SupportTickets\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;
use TresPontosTech\PanelCompany\Filament\Resources\SupportTickets\SupportTicketResource;

class ListSupportTickets extends ListRecords
{
    protected static string $resource = SupportTicketResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label(__('support::resources.support_tickets.actions.create'))
                ->icon(Heroicon::PencilSquare),
        ];
    }
}
