<?php

namespace TresPontosTech\Admin\Filament\Resources\Appointments\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Date;
use TresPontosTech\Appointments\Actions\GetAvailableConsultantsAction;
use TresPontosTech\Appointments\Models\Appointment;

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
                    ->disabled(fn (?Appointment $record): bool => $record instanceof Appointment)
                    ->options(function (Get $get, ?Appointment $record): array {
                        $appointmentAt = $get('appointment_at');

                        if (! $appointmentAt) {
                            return [];
                        }

                        return resolve(GetAvailableConsultantsAction::class)
                            ->handle(
                                appointmentAt: Date::parse($appointmentAt),
                                alwaysIncludeConsultantId: $record?->consultant_id,
                            )
                            ->pluck('name', 'id')
                            ->all();
                    })
                    ->reactive()
                    ->required(fn (?Appointment $record): bool => ! $record instanceof Appointment),
                TextInput::make('meeting_url')
                    ->label(__('appointments::resources.appointments.form.meeting_url'))
                    ->dehydrateStateUsing(function (?string $state): ?string {
                        if (blank($state)) {
                            return $state;
                        }

                        $trimmed = trim($state);

                        return str_starts_with(strtolower($trimmed), 'http')
                            ? $trimmed
                            : sprintf('https://%s', $trimmed);
                    }),
            ]);
    }
}
