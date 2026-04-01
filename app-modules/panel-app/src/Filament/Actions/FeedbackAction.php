<?php

namespace TresPontosTech\App\Filament\Actions;

use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use TresPontosTech\App\Filament\Forms\Components\StarRating;
use TresPontosTech\Appointments\Enums\AppointmentStatus;
use TresPontosTech\Appointments\Models\AppointmentFeedback;

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

        $this->action(function ($record, array $data): void {
            if (filled($record->feedback)) {
                return;
            }

            if (! in_array((int) $data['rating'], range(1, 5), strict: true)) {
                return;
            }

            AppointmentFeedback::query()->create([
                'appointment_id' => $record->id,
                'user_id' => auth()->id(),
                'rating' => $data['rating'],
                'comment' => blank($data['comment']) ? null : $data['comment'],
            ]);

            Notification::make()
                ->title(__('panel-app::resources.appointments.feedback.submitted'))
                ->success()
                ->send();
        });
    }
}
