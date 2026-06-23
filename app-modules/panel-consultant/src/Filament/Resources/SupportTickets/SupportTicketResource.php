<?php

declare(strict_types=1);

namespace TresPontosTech\PanelConsultant\Filament\Resources\SupportTickets;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use TresPontosTech\PanelConsultant\Filament\Resources\SupportTickets\Pages\CreateSupportTicket;
use TresPontosTech\PanelConsultant\Filament\Resources\SupportTickets\Pages\ListSupportTickets;
use TresPontosTech\PanelConsultant\Filament\Resources\SupportTickets\Pages\ViewSupportTicket;
use TresPontosTech\PanelConsultant\Filament\Resources\SupportTickets\Schemas\SupportTicketForm;
use TresPontosTech\PanelConsultant\Filament\Resources\SupportTickets\Schemas\SupportTicketInfolist;
use TresPontosTech\PanelConsultant\Filament\Resources\SupportTickets\Tables\SupportTicketsTable;
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

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('user_id', auth()->id());
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

    public static function form(Schema $schema): Schema
    {
        return SupportTicketForm::configure($schema);
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
            'create' => CreateSupportTicket::route('/create'),
            'view' => ViewSupportTicket::route('/{record}'),
        ];
    }
}
