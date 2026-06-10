<?php

declare(strict_types=1);

namespace TresPontosTech\PanelCompany\Filament\Resources\SupportTickets\Pages;

use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Request;
use TresPontosTech\PanelCompany\Filament\Resources\SupportTickets\SupportTicketResource;
use TresPontosTech\Support\Actions\CreateSupportTicketAction;
use TresPontosTech\Support\DTOs\CreateSupportTicketDTO;
use TresPontosTech\Support\Enums\SupportTicketCategoryEnum;
use TresPontosTech\Support\Models\SupportTicket;

class CreateSupportTicket extends CreateRecord
{
    protected static string $resource = SupportTicketResource::class;

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordCreation(array $data): Model
    {
        $attachment = $data['attachment'] ?? null;

        $dto = new CreateSupportTicketDTO(
            category: $data['category'] instanceof SupportTicketCategoryEnum
                ? $data['category']
                : SupportTicketCategoryEnum::from($data['category']),
            subject: $data['subject'],
            description: $data['description'],
            userId: auth()->id(),
            companyId: filament()->getTenant()?->getKey(),
            url: Request::header('referer'),
            browser: $this->parseBrowser(Request::userAgent()),
            device: $this->parseDevice(Request::userAgent()),
            environment: app()->environment(),
            attachment: $attachment instanceof UploadedFile ? $attachment : null,
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

    private function parseBrowser(?string $userAgent): ?string
    {
        if ($userAgent === null) {
            return null;
        }

        return match (true) {
            str_contains($userAgent, 'Chrome') && ! str_contains($userAgent, 'Edg') => 'Chrome',
            str_contains($userAgent, 'Firefox') => 'Firefox',
            str_contains($userAgent, 'Safari') && ! str_contains($userAgent, 'Chrome') => 'Safari',
            str_contains($userAgent, 'Edg') => 'Edge',
            str_contains($userAgent, 'OPR') => 'Opera',
            default => 'Unknown',
        };
    }

    private function parseDevice(?string $userAgent): ?string
    {
        if ($userAgent === null) {
            return null;
        }

        return match (true) {
            str_contains($userAgent, 'Mobile') || str_contains($userAgent, 'Android') => 'mobile',
            str_contains($userAgent, 'Tablet') || str_contains($userAgent, 'iPad') => 'tablet',
            default => 'desktop',
        };
    }
}
