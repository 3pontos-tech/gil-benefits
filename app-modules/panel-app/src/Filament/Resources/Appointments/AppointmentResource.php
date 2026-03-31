<?php

namespace TresPontosTech\App\Filament\Resources\Appointments;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use TresPontosTech\App\Filament\Resources\Appointments\Pages\CreateAppointment;
use TresPontosTech\App\Filament\Resources\Appointments\Pages\ListAppointments;
use TresPontosTech\App\Filament\Resources\Appointments\Tables\AppointmentsTable;
use TresPontosTech\Appointments\Models\Appointment;
use UnitEnum;

class AppointmentResource extends Resource
{
    protected static ?string $model = Appointment::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::Calendar;

    public static function getModelLabel(): string
    {
        return __('appointments::resources.appointments.label');
    }

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __('panel-admin::resources.navigation_group.appointments');
    }

    public static function getPluralModelLabel(): string
    {
        return __('appointments::resources.appointments.plural');
    }

    public static function getNavigationLabel(): string
    {
        return __('appointments::resources.appointments.navigation');
    }

    public static function table(Table $table): Table
    {
        return AppointmentsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAppointments::route('/'),
            'create' => CreateAppointment::route('/create'),
        ];
    }
}
