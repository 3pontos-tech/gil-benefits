<?php

declare(strict_types=1);

namespace TresPontosTech\PanelAdmin\Filament\Resources\SupportTickets\Pages;

use Filament\Resources\Pages\ViewRecord;
use TresPontosTech\PanelAdmin\Filament\Resources\SupportTickets\Actions\UpdateStatusAction;
use TresPontosTech\PanelAdmin\Filament\Resources\SupportTickets\SupportTicketResource;

class ViewSupportTicket extends ViewRecord
{
    protected static string $resource = SupportTicketResource::class;

    protected function getHeaderActions(): array
    {
        return [
            UpdateStatusAction::make(),
        ];
    }
}
