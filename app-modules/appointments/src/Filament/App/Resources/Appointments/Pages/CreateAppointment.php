<?php

namespace TresPontosTech\Appointments\Filament\App\Resources\Appointments\Pages;

use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use TresPontosTech\Appointments\Actions\BookAppointmentAction;
use TresPontosTech\Appointments\DTO\BookAppointmentDTO;
use TresPontosTech\Appointments\Filament\App\Resources\Appointments\AppointmentResource;
use TresPontosTech\Appointments\Filament\App\Resources\Appointments\Schemas\AppointmentWizard;

class CreateAppointment extends CreateRecord
{
    protected static string $resource = AppointmentResource::class;

    protected string $view = 'filament-panels::pages.page';

    protected Width|string|null $maxContentWidth = '4xl';

    protected static string $layout = 'filament-panels::components.layout.simple';

    protected function getFormActions(): array
    {
        return [];
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                AppointmentWizard::make()
                    ->submitAction(Action::make('appointment-submit')
                        ->label('Book Appointment')
                        ->action('submit')),
            ]);
    }

    public function submit(): void
    {
        $appointmentDTO = BookAppointmentDTO::make(auth()->user()->getKey(), $this->form->getRawState());

        app(BookAppointmentAction::class)->handle($appointmentDTO);

        Notification::make()
            ->title('Appointment booked successfully')
            ->success()
            ->send();
    }
}
