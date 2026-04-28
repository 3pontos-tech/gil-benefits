<?php

declare(strict_types=1);

namespace TresPontosTech\Admin\Filament\Resources\Appointments\Pages;

use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Storage;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Throwable;
use TresPontosTech\Admin\Filament\Resources\Appointments\AppointmentResource;
use TresPontosTech\Appointments\Actions\AssignConsultantAction;
use TresPontosTech\Appointments\Actions\GetAvailableConsultantsAction;
use TresPontosTech\Appointments\Actions\Transitions\TransitionData;
use TresPontosTech\Appointments\Enums\AppointmentStatus;
use TresPontosTech\Appointments\Enums\CancellationActor;
use TresPontosTech\Appointments\Exceptions\SlotUnavailableException;
use TresPontosTech\Appointments\Models\Appointment;
use TresPontosTech\Consultants\Models\Document;
use TresPontosTech\IntegrationGoogleCalendar\Jobs\CreateAppointmentCalendarEventJob;

class ViewAppointment extends ViewRecord
{
    protected static string $resource = AppointmentResource::class;

    protected string $view = 'panel-admin::filament.resources.appointments.pages.view-appointment';

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),

            Action::make('confirm_appointment')
                ->label(__('panel-admin::resources.appointments.actions.confirm_appointment'))
                ->icon(Heroicon::Check)
                ->color('success')
                ->modalSubmitActionLabel(__('panel-admin::resources.appointments.actions.confirm'))
                ->visible(fn (): bool => $this->record->status === AppointmentStatus::Pending)
                ->fillForm(fn (): array => [
                    'appointment_at' => $this->record->appointment_at,
                    'consultant_id' => $this->record->consultant_id,
                ])
                ->schema([
                    DateTimePicker::make('appointment_at')
                        ->label(__('appointments::resources.appointments.table.columns.appointment_at'))
                        ->required()
                        ->live()
                        ->afterStateUpdated(fn (callable $set) => $set('consultant_id', null)),
                    Select::make('consultant_id')
                        ->label(__('appointments::resources.appointments.table.columns.consultant'))
                        ->options(function (Get $get): array {
                            $appointmentAt = $get('appointment_at');

                            if (! $appointmentAt) {
                                return [];
                            }

                            return resolve(GetAvailableConsultantsAction::class)
                                ->handle(
                                    appointmentAt: Date::parse($appointmentAt),
                                    alwaysIncludeConsultantId: $this->record->consultant_id,
                                )
                                ->pluck('name', 'id')
                                ->all();
                        })
                        ->live()
                        ->required(),
                ])
                ->action(function (array $data): void {
                    /** @var Appointment $appointment */
                    $appointment = $this->record;

                    $appointment->update([
                        'appointment_at' => $data['appointment_at'],
                        'consultant_id' => $data['consultant_id'],
                    ]);

                    try {
                        resolve(AssignConsultantAction::class)->handle($appointment);
                    } catch (SlotUnavailableException) {
                        Notification::make()
                            ->title(__('appointments::resources.appointments.exceptions.consultant_unavailable'))
                            ->danger()
                            ->send();

                        return;
                    }

                    $appointment->refresh();
                    $appointment->current_transition->handle(new TransitionData);

                    $appointment->loadMissing('consultant');

                    $consultant = $appointment->consultant;

                    if (filled($consultant) && filled($consultant->email) && blank($appointment->google_event_id)) {
                        try {
                            dispatch_sync(new CreateAppointmentCalendarEventJob($appointment));
                        } catch (Throwable) {
                            Notification::make()
                                ->title(__('panel-admin::resources.appointments.actions.calendar_event_failed'))
                                ->warning()
                                ->send();
                        }
                    }

                    $this->record->refresh();
                }),

            Action::make('complete_appointment')
                ->label(__('panel-admin::resources.appointments.actions.complete_appointment'))
                ->icon(Heroicon::CheckCircle)
                ->color('success')
                ->requiresConfirmation()
                ->visible(fn (): bool => $this->record->status === AppointmentStatus::Active && $this->record->appointment_at->isPast())
                ->action(function (): void {
                    /** @var Appointment $appointment */
                    $appointment = $this->record;
                    $appointment->current_transition->handle(new TransitionData);

                    $this->record->refresh();
                }),

            Action::make('cancel_appointment')
                ->label(__('panel-admin::resources.appointments.actions.cancel_appointment'))
                ->icon(Heroicon::XCircle)
                ->color('danger')
                ->requiresConfirmation()
                ->visible(fn (): bool => $this->record->current_transition->canChange())
                ->action(function (): void {
                    $this->record->current_transition->handle(new TransitionData(
                        cancellationActor: CancellationActor::Admin,
                        cancelledBy: auth()->user(),
                    ));
                    $this->record->refresh();
                }),
        ];
    }

    public function getEmployeeDocuments(): Collection
    {
        $record = $this->getRecord();

        return Document::query()
            ->whereMorphedTo('documentable', $record->user)
            ->get();
    }

    public function getSharedDocuments(): Collection
    {
        $record = $this->getRecord();

        return Document::query()
            ->whereHas('shares', function ($query) use ($record): void {
                $query->where('employee_id', $record->user_id)
                    ->where('active', 1);
            })
            ->with(['media', 'documentable'])
            ->get();
    }

    public function downloadDocumentAction(): Action
    {
        return Action::make('downloadDocument')
            ->iconButton()
            ->icon(Heroicon::ArrowDownTray)
            ->tooltip('Download')
            ->color('gray')
            ->visible(fn (array $arguments): bool => $this->resolveEmployeeDocumentMedia($arguments['documentId'] ?? null) instanceof Media)
            ->url(function (array $arguments): ?string {
                $media = $this->resolveEmployeeDocumentMedia($arguments['documentId'] ?? null);

                return $media instanceof Media ? $this->buildDownloadUrl($media) : null;
            }, shouldOpenInNewTab: true);
    }

    public function downloadSharedDocumentAction(): Action
    {
        return Action::make('downloadSharedDocument')
            ->iconButton()
            ->icon(Heroicon::ArrowDownTray)
            ->tooltip('Download')
            ->color('gray')
            ->visible(fn (array $arguments): bool => $this->resolveSharedDocumentMedia($arguments['documentId'] ?? null) instanceof Media)
            ->url(function (array $arguments): ?string {
                $media = $this->resolveSharedDocumentMedia($arguments['documentId'] ?? null);

                return $media instanceof Media ? $this->buildDownloadUrl($media) : null;
            }, shouldOpenInNewTab: true);
    }

    private function resolveEmployeeDocumentMedia(?string $documentId): ?Media
    {
        if (blank($documentId)) {
            return null;
        }

        $document = Document::query()
            ->whereMorphedTo('documentable', $this->getRecord()->user)
            ->find($documentId);

        return $document?->getFirstMedia('documents');
    }

    private function resolveSharedDocumentMedia(?string $documentId): ?Media
    {
        if (blank($documentId)) {
            return null;
        }

        $appointment = $this->getRecord();

        $document = Document::query()
            ->whereHas('shares', function ($query) use ($appointment): void {
                $query->where('employee_id', $appointment->user_id)
                    ->where('active', 1);
            })
            ->find($documentId);

        return $document?->getFirstMedia('documents');
    }

    private function buildDownloadUrl(Media $media): string
    {
        return Storage::disk($media->disk)->temporaryUrl(
            $media->getPathRelativeToRoot(),
            now()->addMinutes(5),
            ['ResponseContentDisposition' => sprintf('attachment; filename="%s"', $media->file_name)],
        );
    }
}
