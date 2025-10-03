<?php

namespace TresPontosTech\Appointments\Filament\Admin\Resources\Appointments\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
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
                TextInput::make('status')
                    ->required(),
            ]);
    }
}
