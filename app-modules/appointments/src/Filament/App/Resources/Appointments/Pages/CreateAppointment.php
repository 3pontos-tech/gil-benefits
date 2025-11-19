<?php

namespace TresPontosTech\Appointments\Filament\App\Resources\Appointments\Pages;

use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Throwable;
use TresPontosTech\Appointments\Actions\BookAppointmentAction;
use TresPontosTech\Appointments\DTO\BookAppointmentDTO;
use TresPontosTech\Appointments\Filament\App\Resources\Appointments\AppointmentResource;
use TresPontosTech\Appointments\Filament\App\Resources\Appointments\Schemas\AppointmentWizard;

class CreateAppointment extends CreateRecord
{
    protected static string $resource = AppointmentResource::class;

    protected Width|string|null $maxContentWidth = '4xl';

    protected static string $layout = 'filament-panels::components.layout.simple';

    public function mount(): void
    {
        parent::mount();

        /** @var \App\Models\Users\User $user */
        $user = auth()->user();
        if ($user && ! $user->canCreateAppointment()) {
            Notification::make()
                ->title(__('Não é possível agendar agora'))
                ->body(__('Você não possui agendamentos disponíveis neste mês ou já possui uma consultoria em andamento. Finalize a anterior para agendar outra.'))
                ->danger()
                ->send();

            $this->redirectIntended(AppointmentResource::getUrl('index'));
        }
    }

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

        try {
            app(BookAppointmentAction::class)->handle($appointmentDTO);
            Notification::make()
                ->title('Appointment booked successfully')
                ->success()
                ->send();

            $this->redirectIntended(AppointmentResource::getUrl('index'));
        } catch (Throwable) {
            Notification::make()
                ->title('Failed to book appointment')
                ->danger()
                ->send();
        }
    }
}
