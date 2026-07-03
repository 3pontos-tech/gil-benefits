<?php

declare(strict_types=1);

namespace TresPontosTech\PanelApp\Filament\Resources\SupportTickets\Schemas;

use Filament\Infolists\Components\SpatieMediaLibraryImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use TresPontosTech\Support\Enums\SupportTicketCategoryEnum;
use TresPontosTech\Support\Enums\SupportTicketStatusEnum;
use TresPontosTech\Support\Models\SupportTicket;

class SupportTicketInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('support::resources.support_tickets.sections.requester'))
                ->icon(Heroicon::User)
                ->columns(3)
                ->schema([
                    TextEntry::make('protocol')
                        ->label(__('support::resources.support_tickets.fields.protocol'))
                        ->copyable()
                        ->fontFamily('mono'),
                    TextEntry::make('requester_name')
                        ->label(__('support::resources.support_tickets.fields.requester_name'))
                        ->state(fn (SupportTicket $record): ?string => $record->getRequesterName()),
                    TextEntry::make('requester_email')
                        ->label(__('support::resources.support_tickets.fields.requester_email'))
                        ->state(fn (SupportTicket $record): ?string => $record->getRequesterEmail()),
                ]),

            Section::make(__('support::resources.support_tickets.sections.classification'))
                ->icon(Heroicon::Tag)
                ->columns(2)
                ->schema([
                    TextEntry::make('category')
                        ->label(__('support::resources.support_tickets.fields.category'))
                        ->badge()
                        ->formatStateUsing(fn (SupportTicketCategoryEnum $state): string => $state->getLabel())
                        ->icon(fn (SupportTicketCategoryEnum $state): Heroicon => $state->getIcon())
                        ->color(fn (SupportTicketCategoryEnum $state): array|string => $state->getColor()),
                    TextEntry::make('status')
                        ->label(__('support::resources.support_tickets.fields.status'))
                        ->badge()
                        ->formatStateUsing(fn (SupportTicketStatusEnum $state): string => $state->getLabel())
                        ->icon(fn (SupportTicketStatusEnum $state): Heroicon => $state->getIcon())
                        ->color(fn (SupportTicketStatusEnum $state): array|string => $state->getColor()),
                ]),

            Section::make(__('support::resources.support_tickets.sections.details'))
                ->icon(Heroicon::DocumentText)
                ->schema([
                    TextEntry::make('subject')
                        ->label(__('support::resources.support_tickets.fields.subject')),
                    TextEntry::make('description')
                        ->label(__('support::resources.support_tickets.fields.description'))
                        ->columnSpanFull(),
                    TextEntry::make('created_at')
                        ->label(__('support::resources.support_tickets.fields.created_at'))
                        ->dateTime('d/m/Y H:i'),

                    TextEntry::make('updated_at')
                        ->label(__('support::resources.support_tickets.fields.updated_at'))
                        ->dateTime('d/m/Y H:i'),
                ]),

            Section::make(__('support::resources.support_tickets.sections.attachments'))
                ->icon(Heroicon::PaperClip)
                ->visible(fn (SupportTicket $record): bool => $record->getMedia('attachments')->isNotEmpty())
                ->schema([
                    SpatieMediaLibraryImageEntry::make('attachment')
                        ->label('')
                        ->collection('attachments')
                        ->height(220),
                ]),
        ]);
    }
}
