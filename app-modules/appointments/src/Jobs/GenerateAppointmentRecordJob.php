<?php

declare(strict_types=1);

namespace TresPontosTech\Appointments\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Http\UploadedFile;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\RateLimited;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
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
        public string $disk,
        public string $path,
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

        if ($record->generation_started_at !== null) {
            logger()->info('IA :: geração já iniciada, ignorando retry', [
                'record_id' => $this->recordId,
                'started_at' => $record->generation_started_at->toIso8601String(),
            ]);

            return;
        }

        $record->update(['generation_started_at' => now()]);

        $disk = Storage::disk($this->disk);
        $absolutePath = $disk->path($this->path);

        $file = new UploadedFile(
            path: $absolutePath,
            originalName: basename($this->path),
            mimeType: $disk->mimeType($this->path) ?: null,
            test: true,
        );

        try {
            $draft = $generate->execute($file, $record->appointment);
        } catch (Throwable $e) {
            $record->update(['generation_started_at' => null]);

            throw $e;
        }

        $record->update([
            'content' => $draft->content,
            'internal_summary' => $draft->internalSummary,
            'model_used' => $draft->modelUsed,
            'input_tokens' => $draft->inputTokens,
            'output_tokens' => $draft->outputTokens,
        ]);

        $disk->delete($this->path);

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
            'disk' => $this->disk,
            'path' => $this->path,
            'attempts' => $this->attempts(),
            'exception_class' => $e instanceof Throwable ? $e::class : null,
            'message' => $e?->getMessage(),
        ]);
    }
}
