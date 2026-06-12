<?php

declare(strict_types=1);

namespace TresPontosTech\Admin\Filament\Resources\SupportTickets\Actions;

use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use TresPontosTech\Support\Enums\SupportTicketStatusEnum;
use TresPontosTech\Support\Models\SupportTicket;

/**
 * Status-change action shared by the admin ticket table (row action) and the
 * view page (header action). Filament injects $record in both contexts.
 */
final class UpdateStatusAction
{
    public static function make(): Action
    {
        return Action::make('update_status')
            ->label(__('support::resources.support_tickets.actions.update_status'))
            ->icon(Heroicon::ArrowPath)
            ->fillForm(fn (SupportTicket $record): array => ['status' => $record->status])
            ->schema([
                Select::make('status')
                    ->label(__('support::resources.support_tickets.fields.status'))
                    ->options(SupportTicketStatusEnum::class)
                    ->required()
                    ->native(false),
            ])
            ->action(function (SupportTicket $record, array $data): void {
                $status = $data['status'] instanceof SupportTicketStatusEnum
                    ? $data['status']
                    : SupportTicketStatusEnum::from($data['status']);

                $record->update(['status' => $status]);

                Notification::make()
                    ->title(__('support::resources.support_tickets.notifications.status_updated'))
                    ->success()
                    ->send();
            });
    }
}
