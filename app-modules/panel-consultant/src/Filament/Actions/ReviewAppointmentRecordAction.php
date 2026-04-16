<?php

declare(strict_types=1);

namespace TresPontosTech\Consultants\Filament\Actions;

use Filament\Actions\Action;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Gate;
use TresPontosTech\Appointments\Actions\Records\PublishAppointmentRecordAction;
use TresPontosTech\Appointments\Models\Appointment;

class ReviewAppointmentRecordAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'review-appointment-record';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label(fn (Appointment $record): string => $record->record?->isPublished()
            ? __('panel-consultant::resources.appointment_records.review.label_view')
            : __('panel-consultant::resources.appointment_records.review.label_review'));

        $this->icon(Heroicon::PencilSquare);

        $this->visible(fn (Appointment $record): bool => $record->record !== null
            && $record->record->content !== null
            && Gate::allows('update', $record->record));

        $this->authorize(fn (Appointment $record): bool => $record->record !== null
            && $record->record->content !== null
            && Gate::allows('update', $record->record));

        $this->fillForm(fn (Appointment $record): array => [
            'content' => $record->record?->content ?? '',
        ]);

        $this->schema([
            MarkdownEditor::make('content')
                ->label(__('appointments::resources.appointments.records.editor_label'))
                ->required()
                ->columnSpanFull(),
        ]);

        $this->modalWidth('4xl');

        $this->modalSubmitActionLabel(fn (Appointment $record): string => $record->record?->isPublished()
            ? __('panel-consultant::resources.appointment_records.review.submit_save')
            : __('panel-consultant::resources.appointment_records.review.submit_publish'));

        $this->extraModalFooterActions(fn (Action $action, Appointment $record): array => $record->record?->isPublished()
            ? []
            : [
                $action->makeModalSubmitAction(
                    name: 'save_draft',
                    arguments: ['as_draft' => true],
                )
                    ->label(__('panel-consultant::resources.appointment_records.review.save_draft'))
                    ->color('gray'),
            ]);

        $this->action(function (Appointment $record, array $data, array $arguments): void {
            $appointmentRecord = $record->record;

            if ($appointmentRecord === null) {
                return;
            }

            if ($arguments['as_draft'] ?? false) {
                $appointmentRecord->update(['content' => $data['content']]);

                Notification::make()
                    ->success()
                    ->title(__('appointments::resources.appointments.records.notifications.draft_saved.title'))
                    ->send();

                return;
            }

            resolve(PublishAppointmentRecordAction::class)
                ->execute($appointmentRecord, $data['content']);

            Notification::make()
                ->success()
                ->title($appointmentRecord->wasChanged('published_at')
                    ? __('appointments::resources.appointments.records.notifications.published.title')
                    : __('appointments::resources.appointments.records.notifications.updated.title'))
                ->send();
        });
    }
}
