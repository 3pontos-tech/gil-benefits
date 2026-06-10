<?php

declare(strict_types=1);

namespace TresPontosTech\Admin\Filament\Resources\SupportTickets;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use TresPontosTech\Admin\Filament\Resources\SupportTickets\Pages\ListSupportTickets;
use TresPontosTech\Admin\Filament\Resources\SupportTickets\Pages\ViewSupportTicket;
use TresPontosTech\Admin\Filament\Resources\SupportTickets\Schemas\SupportTicketInfolist;
use TresPontosTech\Admin\Filament\Resources\SupportTickets\Tables\SupportTicketsTable;
use TresPontosTech\Support\Models\SupportTicket;
use UnitEnum;

class SupportTicketResource extends Resource
{
    protected static ?string $model = SupportTicket::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedInbox;

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __('support::pages.navigation_group');
    }

    public static function getNavigationLabel(): string
    {
        return __('support::resources.support_tickets.navigation_label');
    }

    public static function getModelLabel(): string
    {
        return __('support::resources.support_tickets.model_label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('support::resources.support_tickets.plural_model_label');
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    public static function infolist(Schema $schema): Schema
    {
        return SupportTicketInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SupportTicketsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSupportTickets::route('/'),
            'view' => ViewSupportTicket::route('/{record}'),
        ];
    }
}
