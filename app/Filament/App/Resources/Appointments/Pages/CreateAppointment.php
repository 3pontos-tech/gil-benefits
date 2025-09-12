<?php

namespace App\Filament\App\Resources\Appointments\Pages;

use App\Action\Appointments\BookAppointmentAction;
use App\DTO\BookAppointmentDTO;
use App\Filament\App\Resources\Appointments\AppointmentResource;
use App\Filament\App\Resources\Appointments\Schemas\AppointmentWizard;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;

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

    public function submit()
    {
        $appointmentDTO = BookAppointmentDTO::make(auth()->user()->getKey(), $this->form->getRawState());

        app(BookAppointmentAction::class)->handle($appointmentDTO);

        Notification::make()
            ->title('Appointment booked successfully')
            ->success()
            ->send();
    }
}
