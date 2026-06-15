<?php

declare(strict_types=1);

namespace TresPontosTech\Admin\Filament\Resources\SupportTickets\Actions;

use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use TresPontosTech\Support\Actions\TransitionSupportTicketStatusAction;
use TresPontosTech\Support\Enums\SupportTicketStatusEnum;
use TresPontosTech\Support\Models\SupportTicket;

/**
 * Status-change action shared by the admin ticket table (row action) and the
 * view page (header action). Filament injects $record in both contexts. The
 * Select offers only the valid next states, and the transition is routed through
 * the domain action so the graph guard and requester notification apply.
 */
final class UpdateStatusAction
{
    public static function make(): Action
    {
        return Action::make('update_status')
            ->label(__('support::resources.support_tickets.actions.update_status'))
            ->icon(Heroicon::ArrowPath)
            // Hidden once the ticket reached a terminal status with no transitions.
            ->visible(fn (SupportTicket $record): bool => $record->status->allowedTransitions() !== [])
            ->schema([
                Select::make('status')
                    ->label(__('support::resources.support_tickets.fields.status'))
                    ->options(fn (SupportTicket $record): array => collect($record->status->allowedTransitions())
                        ->mapWithKeys(fn (SupportTicketStatusEnum $status): array => [
                            $status->value => $status->getLabel(),
                        ])
                        ->all())
                    ->required()
                    ->native(false),
            ])
            ->action(function (SupportTicket $record, array $data): void {
                $status = $data['status'] instanceof SupportTicketStatusEnum
                    ? $data['status']
                    : SupportTicketStatusEnum::from($data['status']);

                resolve(TransitionSupportTicketStatusAction::class)->execute($record, $status);

                Notification::make()
                    ->title(__('support::resources.support_tickets.notifications.status_updated'))
                    ->success()
                    ->send();
            });
    }
}
