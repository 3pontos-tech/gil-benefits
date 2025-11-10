<?php

namespace TresPontosTech\Appointments\Filament\App\Resources\Appointments\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Schemas\Schema;
use TresPontosTech\Appointments\Enums\AppointmentStatus;

class AppointmentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('consultant_id')
                    ->relationship('consultant', 'name')
                    ->required(),
                Select::make('user_id')
                    ->relationship('user', 'name')
                    ->required(),
                DateTimePicker::make('date')
                    ->required(),
                Select::make('status')
                    ->options(AppointmentStatus::class)
                    ->required(),
            ]);
    }
}
