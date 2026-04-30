<?php

declare(strict_types=1);

namespace TresPontosTech\Appointments\Actions\Records;

use Illuminate\Http\UploadedFile;
use Throwable;
use TresPontosTech\Appointments\Models\AppointmentRecord;

final readonly class GenerateAndPersistDraftAction
{
    public function __construct(
        private GenerateRecordDraftAction $generate,
    ) {}

    public function execute(AppointmentRecord $record, UploadedFile $file): void
    {
        try {
            $draft = $this->generate->execute($file, $record->appointment);

            $record->update([
                'content' => $draft->content,
                'internal_summary' => $draft->internalSummary,
                'model_used' => $draft->modelUsed,
                'input_tokens' => $draft->inputTokens,
                'output_tokens' => $draft->outputTokens,
            ]);
        } catch (Throwable $throwable) {
            $record->clearGenerationStart();

            throw $throwable;
        }
    }
}
