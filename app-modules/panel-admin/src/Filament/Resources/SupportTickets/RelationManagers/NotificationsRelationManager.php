<?php

declare(strict_types=1);

namespace TresPontosTech\PanelAdmin\Filament\Resources\SupportTickets\RelationManagers;

use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use TresPontosTech\Support\Actions\ResendTicketNotificationAction;
use TresPontosTech\Support\Enums\TicketNotificationStatusEnum;
use TresPontosTech\Support\Models\SupportTicket;
use TresPontosTech\Support\Models\SupportTicketNotification;

class NotificationsRelationManager extends RelationManager
{
    protected static string $relationship = 'notifications';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('support::resources.support_tickets.sent_notifications.title');
    }

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return $ownerRecord instanceof SupportTicket && $ownerRecord->notifications()->exists();
    }

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('created_at')
                    ->label(__('support::resources.support_tickets.sent_notifications.columns.created_at'))
                    ->dateTime('d/m/Y H:i'),

                TextColumn::make('event')
                    ->label(__('support::resources.support_tickets.sent_notifications.columns.event'))
                    ->badge(),

                TextColumn::make('to_status')
                    ->label(__('support::resources.support_tickets.sent_notifications.columns.transition'))
                    ->formatStateUsing(fn (SupportTicketNotification $record): string => $record->from_status === null
                        ? $record->to_status->getLabel()
                        : $record->from_status->getLabel() . ' → ' . $record->to_status->getLabel()),

                TextColumn::make('status')
                    ->label(__('support::resources.support_tickets.sent_notifications.columns.status'))
                    ->badge(),

                TextColumn::make('attempts')
                    ->label(__('support::resources.support_tickets.sent_notifications.columns.attempts'))
                    ->numeric(),

                TextColumn::make('response_code')
                    ->label(__('support::resources.support_tickets.sent_notifications.columns.response_code'))
                    ->placeholder('—'),

                TextColumn::make('error')
                    ->label(__('support::resources.support_tickets.sent_notifications.columns.error'))
                    ->placeholder('—')
                    ->limit(60)
                    ->tooltip(fn (SupportTicketNotification $record): ?string => $record->error),

                TextColumn::make('message')
                    ->label(__('support::resources.support_tickets.sent_notifications.columns.message'))
                    ->wrap()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('sent_at')
                    ->label(__('support::resources.support_tickets.sent_notifications.columns.sent_at'))
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('—'),
            ])
            ->recordActions([
                Action::make('resend')
                    ->label(__('support::resources.support_tickets.sent_notifications.actions.resend.label'))
                    ->icon(Heroicon::ArrowPath)
                    ->requiresConfirmation()
                    ->visible(fn (SupportTicketNotification $record): bool => $record->status === TicketNotificationStatusEnum::Failed)
                    ->action(function (SupportTicketNotification $record): void {
                        resolve(ResendTicketNotificationAction::class)->execute($record);

                        Notification::make()
                            ->success()
                            ->title(__('support::resources.support_tickets.sent_notifications.actions.resend.notification'))
                            ->send();
                    }),
            ])
            ->emptyStateHeading(__('support::resources.support_tickets.sent_notifications.empty'));
    }
}
