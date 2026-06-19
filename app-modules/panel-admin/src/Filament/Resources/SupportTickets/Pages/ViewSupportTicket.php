<?php

declare(strict_types=1);

namespace TresPontosTech\Admin\Filament\Resources\SupportTickets\Pages;

use Filament\Resources\Pages\ViewRecord;
use TresPontosTech\Admin\Filament\Resources\SupportTickets\Actions\UpdateStatusAction;
use TresPontosTech\Admin\Filament\Resources\SupportTickets\SupportTicketResource;

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
