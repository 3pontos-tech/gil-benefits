<?php

namespace TresPontosTech\PanelApp\Filament\Actions;

use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use TresPontosTech\Appointments\Actions\SubmitAppointmentFeedbackAction;
use TresPontosTech\Appointments\Enums\AppointmentStatus;
use TresPontosTech\Appointments\Exceptions\AppointmentFeedbackException;
use TresPontosTech\Appointments\Models\Appointment;
use TresPontosTech\PanelApp\Filament\Forms\Components\StarRating;

class FeedbackAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'feedback';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label(__('panel-app::resources.appointments.feedback.action_label'));
        $this->icon('heroicon-o-star');
        $this->color('warning');
        $this->visible(fn ($record): bool => $record->status === AppointmentStatus::Completed && blank($record->feedback));
        $this->modalHeading(__('panel-app::resources.appointments.feedback.modal_heading'));
        $this->modalDescription(__('panel-app::resources.appointments.feedback.modal_description'));
        $this->modalSubmitActionLabel(__('panel-app::resources.appointments.feedback.submit'));

        $this->form([
            StarRating::make('rating')
                ->label(__('panel-app::resources.appointments.feedback.rating'))
                ->required()
                ->rules(['integer', 'min:1', 'max:5']),
            Textarea::make('comment')
                ->label(__('panel-app::resources.appointments.feedback.comment'))
                ->rows(3),
        ]);

        $this->action(function (Appointment $record, array $data): void {
            try {
                resolve(SubmitAppointmentFeedbackAction::class)->handle($record, auth()->user(), (int) $data['rating'], $data['comment'] ?? null);
            } catch (AppointmentFeedbackException) {
                return;
            }

            Notification::make()
                ->title(__('panel-app::resources.appointments.feedback.submitted'))
                ->success()
                ->send();
        });
    }
}
