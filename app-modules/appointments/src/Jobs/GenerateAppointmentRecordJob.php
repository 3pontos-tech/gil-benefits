<?php

declare(strict_types=1);

namespace TresPontosTech\Appointments\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\RateLimited;
use Illuminate\Queue\SerializesModels;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Throwable;
use TresPontosTech\Appointments\Actions\Records\GenerateRecordDraftAction;
use TresPontosTech\Appointments\Models\AppointmentRecord;

class GenerateAppointmentRecordJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $timeout = 180;

    public function __construct(
        public string $recordId,
        public string $temporaryFilename,
    ) {}

    public function tries(): int
    {
        return 2;
    }

    /**
     * @return array<int, int>
     */
    public function backoff(): array
    {
        return [15];
    }

    /**
     * @return array<int, object>
     */
    public function middleware(): array
    {
        return [new RateLimited('appointment-record-ai')];
    }

    public function handle(GenerateRecordDraftAction $generate): void
    {
        $record = AppointmentRecord::with([
            'appointment.user',
            'appointment.consultant.user',
        ])->findOrFail($this->recordId);

        $file = TemporaryUploadedFile::createFromLivewire($this->temporaryFilename);

        $draft = $generate->execute($file, $record->appointment);

        $record->update([
            'content' => $draft->content,
            'internal_summary' => $draft->internalSummary,
            'model_used' => $draft->modelUsed,
            'input_tokens' => $draft->inputTokens,
            'output_tokens' => $draft->outputTokens,
        ]);

        // TODO: notificar o consultor pela central de notificações in-app (Filament database notification)
        // quando a feature de central de notificações for implementada.
        // Notification::make()
        //     ->success()
        //     ->title(__('appointments::resources.appointments.records.notifications.ready.title'))
        //     ->body(__('appointments::resources.appointments.records.notifications.ready.body', [
        //         'user' => $record->appointment->user->name,
        //     ]))
        //     ->sendToDatabase($record->appointment->consultant->user)
        //     ->send();
    }

    public function failed(?Throwable $e): void
    {
        logger()->error('IA :: job de geração falhou definitivamente após retries', [
            'record_id' => $this->recordId,
            'temporary_filename' => $this->temporaryFilename,
            'attempts' => $this->attempts(),
            'exception_class' => $e instanceof Throwable ? $e::class : null,
            'message' => $e?->getMessage(),
        ]);

        $record = AppointmentRecord::with('appointment.consultant.user')
            ->find($this->recordId);

        if (! $record) {
            return;
        }

        // TODO: notificar o consultor da falha pela central de notificações in-app (Filament database notification)
        // quando a feature de central de notificações for implementada.
        // $reason = match (true) {
        //     $e instanceof RecordGenerationFailedException
        //         && $e->reason === RecordGenerationFailedException::REASON_UNREADABLE_DOCUMENT => 'unreadable',
        //     $e instanceof RecordGenerationFailedException => 'generation',
        //     default => 'unexpected',
        // };
        // Notification::make()
        //     ->danger()
        //     ->title(__('appointments::resources.appointments.records.notifications.failed.title'))
        //     ->body(__("appointments::resources.appointments.records.notifications.failed.body.{$reason}"))
        //     ->sendToDatabase($record->appointment->consultant->user)
        //     ->send();

        $record->forceDelete();
    }
}
