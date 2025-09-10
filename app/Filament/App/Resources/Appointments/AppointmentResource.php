<?php

namespace App\Filament\App\Resources\Appointments;

use App\Filament\App\Resources\Appointments\Pages\CreateAppointment;
use App\Filament\App\Resources\Appointments\Pages\EditAppointment;
use App\Filament\App\Resources\Appointments\Pages\ListAppointments;
use App\Filament\App\Resources\Appointments\Pages\ViewAppointment;
use App\Filament\App\Resources\Appointments\Schemas\AppointmentForm;
use App\Filament\App\Resources\Appointments\Schemas\AppointmentInfolist;
use App\Filament\App\Resources\Appointments\Tables\AppointmentsTable;
use App\Models\Appointment;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

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
