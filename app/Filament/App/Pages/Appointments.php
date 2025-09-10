<?php

namespace App\Filament\App\Pages;

use App\Filament\App\Resources\Appointments\Schemas\AppointmentWizard;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Support\Enums\Width;

class Appointments extends Page implements HasSchemas
{
    use InteractsWithForms;

    protected static string|null|BackedEnum $navigationIcon = 'heroicon-o-calendar-days';

    protected string $view = 'filament.app.pages.appointments';

    protected static ?string $slug = '123';

    public function start(): void
    {
        Notification::make()
            ->title('Appointment booked successfully')
            ->success()
            ->send();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('appointments')
                ->label('Book Appointment')
                ->schema([
                    AppointmentWizard::make(),
                ])
                ->modalSubmitAction(false)
                ->modalCancelAction(false)
                ->modalHeading('Book a new appointment')
                ->modalWidth(Width::ExtraLarge)
        ];
    }
}
