<?php

declare(strict_types=1);

namespace TresPontosTech\PanelAdmin\Filament\Resources\Appointments\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Date;
use TresPontosTech\Appointments\Actions\GetAvailableConsultantsAction;
use TresPontosTech\Appointments\Models\Appointment;

class AppointmentScheduleFields
{
    /**
     * @return array<int, DateTimePicker|Select>
     */
    public static function make(bool $editOnly = true): array
    {
        $consultantField = Select::make('consultant_id')
            ->label(__('appointments::resources.appointments.table.columns.consultant'))
            ->hintIcon(fn (?Appointment $record): ?Heroicon => $record instanceof Appointment && $record->isActive() ? Heroicon::InformationCircle : null)
            ->searchable()
            ->hint(fn (?Appointment $record): ?string => $record instanceof Appointment && $record->isActive()
                ? (string) __('panel-admin::resources.appointments.hints.consultant_removal')
                : null)
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
            ->reactive();

        if ($editOnly) {
            $consultantField->visibleOn('edit');
        }

        return [
            DateTimePicker::make('appointment_at')
                ->label(__('appointments::resources.appointments.table.columns.appointment_at'))
                ->required()
                ->minDate(now())
                ->reactive()
                ->afterStateUpdated(fn (callable $set) => $set('consultant_id', null)),
            $consultantField,
        ];
    }
}
