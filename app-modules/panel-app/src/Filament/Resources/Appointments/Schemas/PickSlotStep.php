<?php

declare(strict_types=1);

namespace TresPontosTech\PanelApp\Filament\Resources\Appointments\Schemas;

use Filament\Forms\Components\ViewField;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Utilities\Get;
use TresPontosTech\Appointments\Models\Appointment;

/**
 * Campos de data e horário compartilhados pelos wizards de agendamento e de
 * reagendamento: o calendário à esquerda e a grade de horários à direita,
 * ambos alimentados pela mesma disponibilidade do fluxo de criação original.
 */
class PickSlotStep
{
    /**
     * @return array<Component>
     */
    public static function fields(): array
    {
        return [
            Grid::make(['default' => 1, 'md' => 5])
                ->schema([
                    ViewField::make('date')
                        ->label(__('appointments::resources.appointments.wizard.labels.date'))
                        ->hiddenLabel()
                        ->view('filament.app.appointments.wizard.calendar-field', [
                            'minDate' => fn (): string => now()->addDays(Appointment::BOOKING_LEAD_DAYS)->toDateString(),
                        ])
                        ->required()
                        ->columnSpan(['md' => 3]),

                    ViewField::make('appointment_at')
                        ->label(__('appointments::resources.appointments.wizard.labels.available_times'))
                        ->hiddenLabel()
                        ->view('filament.app.appointments.wizard.slots-field', [
                            'slots' => fn (Get $get): array => AppointmentWizard::availableSlots($get('date')),
                        ])
                        ->required()
                        ->columnSpan(['md' => 2]),
                ]),
        ];
    }
}
