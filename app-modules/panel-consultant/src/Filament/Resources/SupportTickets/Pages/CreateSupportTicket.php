<?php

declare(strict_types=1);

namespace TresPontosTech\Consultants\Filament\Resources\SupportTickets\Pages;

use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Request;
use TresPontosTech\Consultants\Filament\Resources\SupportTickets\SupportTicketResource;
use TresPontosTech\Support\Actions\CreateSupportTicketAction;
use TresPontosTech\Support\DTOs\CreateSupportTicketDTO;
use TresPontosTech\Support\Models\SupportTicket;

class CreateSupportTicket extends CreateRecord
{
    protected static string $resource = SupportTicketResource::class;

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordCreation(array $data): Model
    {
        $userId = auth()->id();

        $dto = CreateSupportTicketDTO::fromFormState(
            $data,
            userId: $userId !== null ? (string) $userId : null,
            companyId: filament()->getTenant()?->getKey(),
            url: Request::header('referer'),
            userAgent: Request::userAgent(),
            environment: app()->environment(),
        );

        return resolve(CreateSupportTicketAction::class)->execute($dto);
    }

    protected function getCreatedNotification(): ?Notification
    {
        /** @var SupportTicket $record */
        $record = $this->record;

        return Notification::make()
            ->success()
            ->title(__('support::resources.support_tickets.notifications.created', [
                'protocol' => $record->protocol,
            ]));
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
