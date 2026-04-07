<?php

namespace TresPontosTech\App\Filament\Resources\Appointments\Pages;

use App\Models\Users\User;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Throwable;
use TresPontosTech\App\Filament\Resources\Appointments\AppointmentResource;
use TresPontosTech\App\Filament\Resources\Appointments\Schemas\AppointmentWizard;
use TresPontosTech\Appointments\Actions\BookAppointmentAction;
use TresPontosTech\Appointments\DTO\BookAppointmentDTO;
use TresPontosTech\Appointments\Exceptions\SlotUnavailableException;

class CreateAppointment extends CreateRecord
{
    protected static string $resource = AppointmentResource::class;

    protected Width|string|null $maxContentWidth = '4xl';

    protected static string $layout = 'filament-panels::components.layout.simple';

    public function mount(): void
    {
        parent::mount();

        /** @var User $user */
        $user = auth()->user();
        if ($user && ! $user->canCreateAppointment()) {
            Notification::make()
                ->title(__('panel-app::resources.appointments.pages.create.cannot_book_now'))
                ->body(__('panel-app::resources.appointments.pages.create.no_appointments_available'))
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
                        ->label(__('panel-app::resources.appointments.pages.create.book_appointment'))
                        ->action('submit')),
            ]);
    }

    public function submit(): void
    {
        $appointmentDTO = BookAppointmentDTO::make(auth()->user()->getKey(), $this->form->getRawState());

        try {
            resolve(BookAppointmentAction::class)->handle($appointmentDTO);
            Notification::make()
                ->title(__('panel-app::resources.appointments.pages.create.booked_successfully'))
                ->success()
                ->send();

            $this->redirectIntended(AppointmentResource::getUrl('index'));
        } catch (SlotUnavailableException $exception) {
            Notification::make()
                ->title($exception->getMessage())
                ->danger()
                ->send();
        } catch (Throwable $throwable) {
            Notification::make()
                ->title(__('panel-app::resources.appointments.pages.create.booking_failed'))
                ->danger()
                ->send();
        }
    }
}
