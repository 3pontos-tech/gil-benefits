<?php

namespace App\Filament\App\Resources\Appointments\Schemas;

use App\Enums\AppointmentStatus;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Schemas\Schema;

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
                Select::make('voucher_id')
                    ->relationship('voucher', 'id')
                    ->required(),
                DateTimePicker::make('date')
                    ->required(),
                Select::make('status')
                    ->options(AppointmentStatus::class)
                    ->required(),
            ]);
    }
}
