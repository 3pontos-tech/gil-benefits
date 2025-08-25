<?php

namespace App\Filament\App\Pages;

use App\Filament\Wizard\AppointmentWizard;
use App\Models\Appointment;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Pages\Page;
use Filament\Schemas\Contracts\HasSchemas;

class Appointments extends Page implements HasSchemas
{
    use InteractsWithForms;

    protected static string|null|BackedEnum $navigationIcon = 'heroicon-o-calendar-days';

    protected string $view = 'filament.app.pages.appointments';

    public function submit(): void
    {
        // Aqui você pode salvar no banco
        // Appointment::create($this->data);

        // Exemplo:
        // Appointment::create([
        //     'consultant_id' => $this->data['consultant_id'],
        //     'voucher_id' => $this->data['voucher_id'] ?? null,
        //     'date' => $this->data['date'].' '.$this->data['time'],
        //     'status' => 'pending',
        // ]);

        $this->notify('success', 'Appointment booked successfully.');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('appointments')
                ->label('Book Appointment')
                ->schema([
                    AppointmentWizard::make(),
                ])
                ->modalHeading('Book a new appointment')
                ->action(function (array $data) {
                    // Salvar no banco
                    Appointment::create([
                        'consultant_id' => $data['consultant_id'],
                        'voucher_id' => $data['voucher_id'] ?? null,
                        'date' => $data['date'] . ' ' . $data['time'],
                        'status' => 'pending',
                    ]);
                }),
        ];
    }
}
