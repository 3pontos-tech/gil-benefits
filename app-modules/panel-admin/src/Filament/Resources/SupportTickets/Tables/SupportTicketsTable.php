<?php

declare(strict_types=1);

namespace TresPontosTech\Admin\Filament\Resources\SupportTickets\Tables;

use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Contracts\Database\Eloquent\Builder;
use TresPontosTech\Support\Enums\SupportTicketCategoryEnum;
use TresPontosTech\Support\Enums\SupportTicketStatusEnum;
use TresPontosTech\Support\Models\SupportTicket;

class SupportTicketsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('protocol')
                    ->label(__('support::resources.support_tickets.columns.protocol'))
                    ->searchable()
                    ->copyable()
                    ->fontFamily('mono'),

                TextColumn::make('requester')
                    ->label(__('support::resources.support_tickets.columns.requester'))
                    ->state(fn (SupportTicket $record): ?string => $record->getRequesterName())
                    ->searchable(query: fn (Builder $query, string $search): Builder => $query
                        ->whereHas('user', fn (Builder $q) => $q->where('name', 'like', sprintf('%%%s%%', $search)))
                        ->orWhere('visitor_name', 'like', sprintf('%%%s%%', $search))
                    ),

                TextColumn::make('category')
                    ->label(__('support::resources.support_tickets.columns.category'))
                    ->badge()
                    ->formatStateUsing(fn (SupportTicketCategoryEnum $state): string => $state->getLabel())
                    ->icon(fn (SupportTicketCategoryEnum $state): Heroicon => $state->getIcon())
                    ->color(fn (SupportTicketCategoryEnum $state): array|string => $state->getColor()),

                TextColumn::make('subject')
                    ->label(__('support::resources.support_tickets.columns.subject'))
                    ->searchable()
                    ->limit(60),

                TextColumn::make('status')
                    ->label(__('support::resources.support_tickets.columns.status'))
                    ->badge()
                    ->formatStateUsing(fn (SupportTicketStatusEnum $state): string => $state->getLabel())
                    ->icon(fn (SupportTicketStatusEnum $state): Heroicon => $state->getIcon())
                    ->color(fn (SupportTicketStatusEnum $state): array|string => $state->getColor()),

                TextColumn::make('created_at')
                    ->label(__('support::resources.support_tickets.columns.created_at'))
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->label(__('support::resources.support_tickets.columns.status'))
                    ->options(SupportTicketStatusEnum::class),

                SelectFilter::make('category')
                    ->label(__('support::resources.support_tickets.columns.category'))
                    ->options(SupportTicketCategoryEnum::class),
            ])
            ->recordActions([
                ViewAction::make(),

                Action::make('update_status')
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
                    }),
            ]);
    }
}
