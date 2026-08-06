<?php

declare(strict_types=1);

namespace TresPontosTech\PanelAdmin\Filament\Resources\AppointmentFeedbacks;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use TresPontosTech\Appointments\Models\AppointmentFeedback;
use TresPontosTech\PanelAdmin\Filament\Resources\AppointmentFeedbacks\Pages\ListAppointmentFeedbacks;
use TresPontosTech\PanelAdmin\Filament\Resources\AppointmentFeedbacks\Pages\ViewAppointmentFeedback;
use TresPontosTech\PanelAdmin\Filament\Resources\AppointmentFeedbacks\Schemas\AppointmentFeedbackInfolist;
use TresPontosTech\PanelAdmin\Filament\Resources\AppointmentFeedbacks\Tables\AppointmentFeedbacksTable;
use UnitEnum;

class AppointmentFeedbackResource extends Resource
{
    protected static ?string $model = AppointmentFeedback::class;

    protected static ?string $slug = 'appointment-feedbacks';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedStar;

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __('panel-admin::resources.navigation_group.appointments');
    }

    public static function getNavigationLabel(): string
    {
        return __('panel-admin::resources.appointment_feedbacks.navigation_label');
    }

    public static function getModelLabel(): string
    {
        return __('panel-admin::resources.appointment_feedbacks.model_label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('panel-admin::resources.appointment_feedbacks.plural_model_label');
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

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['user', 'appointment.consultant', 'appointment.company'])
            ->whereHas('appointment');
    }

    public static function infolist(Schema $schema): Schema
    {
        return AppointmentFeedbackInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AppointmentFeedbacksTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAppointmentFeedbacks::route('/'),
            'view' => ViewAppointmentFeedback::route('/{record}'),
        ];
    }
}
