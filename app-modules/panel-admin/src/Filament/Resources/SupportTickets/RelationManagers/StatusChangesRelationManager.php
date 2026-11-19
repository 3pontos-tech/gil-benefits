<?php

declare(strict_types=1);

namespace TresPontosTech\PanelAdmin\Filament\Resources\SupportTickets\RelationManagers;

use BackedEnum;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use TresPontosTech\Support\Models\SupportTicketStatusChange;

class StatusChangesRelationManager extends RelationManager
{
    protected static string $relationship = 'statusChanges';

    protected static string|BackedEnum|null $icon = Heroicon::ClipboardDocumentList;

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('support::resources.support_tickets.status_history.title');
    }

    /**
     * created_at has second precision, so a ticket opened and closed in the same
     * second ties; the id (UUIDv7, time-ordered) breaks the tie in insertion order.
     */
    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with('actor'))
            ->defaultSort(fn (Builder $query): Builder => $query->latest()->orderByDesc('id'))
            ->columns([
                TextColumn::make('created_at')
                    ->label(__('support::resources.support_tickets.status_history.columns.created_at'))
                    ->dateTime('d/m/Y H:i'),

                TextColumn::make('to_status')
                    ->label(__('support::resources.support_tickets.status_history.columns.transition'))
                    ->formatStateUsing(fn (SupportTicketStatusChange $record): string => $record->from_status === null
                        ? $record->to_status->getLabel()
                        : $record->from_status->getLabel() . ' → ' . $record->to_status->getLabel()),

                TextColumn::make('source')
                    ->label(__('support::resources.support_tickets.status_history.columns.source'))
                    ->badge(),

                TextColumn::make('actor.name')
                    ->label(__('support::resources.support_tickets.status_history.columns.actor'))
                    ->placeholder('—'),

                TextColumn::make('reason')
                    ->label(__('support::resources.support_tickets.status_history.columns.reason'))
                    ->placeholder('—')
                    ->wrap(),
            ])
            ->emptyStateHeading(__('support::resources.support_tickets.status_history.empty'));
    }
}
