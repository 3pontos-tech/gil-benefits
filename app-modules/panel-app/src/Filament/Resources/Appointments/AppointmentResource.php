<?php

namespace TresPontosTech\PanelApp\Filament\Resources\Appointments;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use TresPontosTech\Appointments\Models\Appointment;
use TresPontosTech\PanelApp\Filament\Resources\Appointments\Pages\CreateAppointment;
use TresPontosTech\PanelApp\Filament\Resources\Appointments\Pages\ListAppointments;
use TresPontosTech\PanelApp\Filament\Resources\Appointments\Tables\AppointmentsTable;
use UnitEnum;

class AppointmentResource extends Resource
{
    protected static ?string $model = Appointment::class;

    /**
     * Appointments belong to the beneficiary, not to the company they were booked
     * under: an employee sees their whole history regardless of the tenant they are
     * browsing. The table already narrows to the signed-in user, and company_id only
     * records which plan paid for the session (see User::coveringCompanyId()).
     */
    protected static bool $isScopedToTenant = false;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::Calendar;

    protected static ?int $navigationSort = 1;

    public static function getModelLabel(): string
    {
        return __('appointments::resources.appointments.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('appointments::resources.appointments.plural');
    }

    public static function getNavigationLabel(): string
    {
        return __('appointments::resources.appointments.navigation');
    }

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __('panel-app::navigation.groups.appointments.label');
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
