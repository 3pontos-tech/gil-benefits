<?php

namespace TresPontosTech\PanelAdmin\Filament\Resources\Appointments;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use TresPontosTech\Appointments\Models\Appointment;
use TresPontosTech\PanelAdmin\Filament\Resources\Appointments\Pages\CreateAppointment;
use TresPontosTech\PanelAdmin\Filament\Resources\Appointments\Pages\EditAppointment;
use TresPontosTech\PanelAdmin\Filament\Resources\Appointments\Pages\ListAppointments;
use TresPontosTech\PanelAdmin\Filament\Resources\Appointments\Pages\ViewAppointment;
use TresPontosTech\PanelAdmin\Filament\Resources\Appointments\Schemas\AppointmentForm;
use TresPontosTech\PanelAdmin\Filament\Resources\Appointments\Tables\AppointmentsTable;

class AppointmentResource extends Resource
{
    protected static ?string $model = Appointment::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDateRange;

    public static function getNavigationLabel(): string
    {
        return __('panel-admin::resources.appointments.navigation_label');
    }

    public static function getModelLabel(): string
    {
        return __('panel-admin::resources.appointments.model_label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('panel-admin::resources.appointments.plural_model_label');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('panel-admin::resources.navigation_group.appointments');
    }

    public static function form(Schema $schema): Schema
    {
        return AppointmentForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AppointmentsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAppointments::route('/'),
            'create' => CreateAppointment::route('/create'),
            'view' => ViewAppointment::route('/{record}'),
            'edit' => EditAppointment::route('/{record}/edit'),
        ];
    }
}
