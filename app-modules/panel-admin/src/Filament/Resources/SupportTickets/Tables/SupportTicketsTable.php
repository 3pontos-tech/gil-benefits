<?php

declare(strict_types=1);

namespace TresPontosTech\PanelAdmin\Filament\Resources\SupportTickets\Tables;

use Filament\Actions\ViewAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Contracts\Database\Eloquent\Builder;
use TresPontosTech\PanelAdmin\Filament\Resources\SupportTickets\Actions\UpdateStatusAction;
use TresPontosTech\Support\Enums\SupportTicketCategoryEnum;
use TresPontosTech\Support\Enums\SupportTicketStatusEnum;
use TresPontosTech\Support\Enums\TicketChannelEnum;
use TresPontosTech\Support\Models\Builders\SupportTicketBuilder;
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

                TextColumn::make('channel')
                    ->label(__('support::resources.support_tickets.columns.channel'))
                    ->badge()
                    ->color('gray')
                    ->toggleable(),

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

                SelectFilter::make('channel')
                    ->label(__('support::resources.support_tickets.columns.channel'))
                    ->options(TicketChannelEnum::class),

                Filter::make('reopened')
                    ->label(__('support::resources.support_tickets.filters.reopened'))
                    ->toggle()
                    ->query(fn (SupportTicketBuilder $query): SupportTicketBuilder => $query->reopened()),
            ])
            ->recordActions([
                ViewAction::make(),
                UpdateStatusAction::make(),
            ]);
    }
}
