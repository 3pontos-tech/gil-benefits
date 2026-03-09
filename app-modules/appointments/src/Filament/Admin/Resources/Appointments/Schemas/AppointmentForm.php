<?php

namespace TresPontosTech\Appointments\Filament\Admin\Resources\Appointments\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Date;
use TresPontosTech\Appointments\Enums\AppointmentStatus;
use TresPontosTech\Appointments\Models\Appointment;
use TresPontosTech\Consultants\Models\Consultant;

class AppointmentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('user_id')
                    ->label(__('appointments::resources.appointments.table.columns.user'))
                    ->relationship('user', 'name')
                    ->required(),
                DateTimePicker::make('appointment_at')
                    ->label(__('appointments::resources.appointments.table.columns.appointment_at'))
                    ->required()
                    ->reactive()
                    ->afterStateUpdated(fn (callable $set) => $set('consultant_id', null)),
                Select::make('consultant_id')
                    ->label(__('appointments::resources.appointments.table.columns.consultant'))
                    ->options(function (Get $get, ?Appointment $record) {
                        $appointmentAt = $get('appointment_at');

                        if (! $appointmentAt) {
                            return [];
                        }

                        $date = Date::parse($appointmentAt);

                        return Consultant::all()
                            ->filter(function (Consultant $c) use ($date, $record): bool {

                                if ($record?->consultant_id === $c->getKey()) {
                                    return true;
                                }

                                return $c->isBookableAtTime(
                                    $date->format('Y-m-d'),
                                    $date->format('H:i'),
                                    $date->copy()->addHour()->format('H:i'),
                                );
                            })
                            ->pluck('name', 'id');
                    })
                    ->reactive()
                    ->required(),
                Select::make('status')
                    ->options(AppointmentStatus::class)
                    ->required(),
                TextInput::make('meeting_url')
                    ->label(__('appointments::resources.appointments.form.meeting_url'))
                    ->dehydrateStateUsing(fn (?string $state): ?string => filled($state) && ! str_starts_with($state, 'http')
                        ? 'https://' . $state
                        : $state
                    ),
            ]);
    }
}
