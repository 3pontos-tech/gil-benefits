<?php

declare(strict_types=1);

namespace TresPontosTech\PanelAdmin\Filament\Resources\SupportTickets\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class DestinationsRelationManager extends RelationManager
{
    protected static string $relationship = 'destinations';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('support::resources.support_tickets.destinations.title');
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('type')
                    ->label(__('support::resources.support_tickets.destinations.columns.type'))
                    ->badge(),

                TextColumn::make('channel')
                    ->label(__('support::resources.support_tickets.destinations.columns.channel'))
                    ->badge(),

                TextColumn::make('status')
                    ->label(__('support::resources.support_tickets.destinations.columns.status'))
                    ->badge(),

                TextColumn::make('reference_id')
                    ->label(__('support::resources.support_tickets.destinations.columns.reference_id'))
                    ->placeholder('—')
                    ->copyable(),

                TextColumn::make('created_at')
                    ->label(__('support::resources.support_tickets.destinations.columns.created_at'))
                    ->dateTime('d/m/Y H:i'),
            ])
            ->emptyStateHeading(__('support::resources.support_tickets.destinations.empty'));
    }
}
