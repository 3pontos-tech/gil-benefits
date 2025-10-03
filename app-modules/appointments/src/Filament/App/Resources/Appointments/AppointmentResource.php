<?php

namespace TresPontosTech\Appointments\Filament\App\Resources\Appointments;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use TresPontosTech\Appointments\Filament\App\Resources\Appointments\Pages\CreateAppointment;
use TresPontosTech\Appointments\Filament\App\Resources\Appointments\Pages\EditAppointment;
use TresPontosTech\Appointments\Filament\App\Resources\Appointments\Pages\ListAppointments;
use TresPontosTech\Appointments\Filament\App\Resources\Appointments\Pages\ViewAppointment;
use TresPontosTech\Appointments\Filament\App\Resources\Appointments\Schemas\AppointmentInfolist;
use TresPontosTech\Appointments\Filament\App\Resources\Appointments\Tables\AppointmentsTable;
use TresPontosTech\Appointments\Models\Appointment;

class AppointmentResource extends Resource
{
    protected static ?string $model = Appointment::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function infolist(Schema $schema): Schema
    {
        return AppointmentInfolist::configure($schema);
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
