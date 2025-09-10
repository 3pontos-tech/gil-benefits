<?php

namespace App\Filament\App\Resources\Appointments\Pages;

use App\Filament\App\Resources\Appointments\AppointmentResource;
use App\Filament\App\Resources\Appointments\Schemas\AppointmentWizard;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Filament\Schemas\Schema;

class CreateAppointment extends CreateRecord
{
    protected static string $resource = AppointmentResource::class;

    protected string $view = 'filament-panels::pages.page';

    public ?array $formData = [];

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
        Notification::make()
            ->title('Appointment booked successfully')
            ->success()
            ->send();
    }
}
