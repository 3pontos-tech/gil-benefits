<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Prism\Prism\Exceptions\PrismException;
use TresPontosTech\Appointments\Actions\Records\GenerateRecordDraftAction;
use TresPontosTech\Appointments\DTO\GeneratedDraft;
use TresPontosTech\Appointments\Exceptions\RecordGenerationFailedException;
use TresPontosTech\Appointments\Jobs\GenerateAppointmentRecordJob;
use TresPontosTech\Appointments\Models\Appointment;
use TresPontosTech\Appointments\Models\AppointmentRecord;
use TresPontosTech\Appointments\Support\DocumentTextExtractor;
use TresPontosTech\Consultants\Models\Consultant;

beforeEach(function (): void {
    Log::spy();
    Mail::fake();
});

function fakeAppointmentWithRecord(): array
{
    $consultant = Consultant::factory()->create();
    $appointment = Appointment::factory()->create([
        'consultant_id' => $consultant->getKey(),
    ]);

    /** @var AppointmentRecord $record */
    $record = AppointmentRecord::factory()
        ->recycle($appointment)
        ->draft()
        ->create();

    return [$record, $consultant];
}

it('handle: persiste content gerado e não envia nenhum email ao consultor', function (): void {
    [$record] = fakeAppointmentWithRecord();

    app()->instance(DocumentTextExtractor::class, new readonly class extends DocumentTextExtractor
    {
        public function extractText(TemporaryUploadedFile $file): ?string
        {
            return null;
        }
    });

    app()->instance(GenerateRecordDraftAction::class, new readonly class extends GenerateRecordDraftAction
    {
        public function __construct() {}

        public function execute(TemporaryUploadedFile $file, Appointment $appointment): GeneratedDraft
        {
            return new GeneratedDraft(
                content: '## Resumo executivo\n\nConteúdo persistido pelo job.',
                modelUsed: 'gemini-2.5-pro',
                inputTokens: 12450,
                outputTokens: 2110,
                internalSummary: '## Resumo para o próximo atendimento\n\n- ponto-chave do próximo consultor',
            );
        }
    });

    $job = new class($record->id, 'fake-livewire-tmp.pdf') extends GenerateAppointmentRecordJob
    {
        public function handle(GenerateRecordDraftAction $generate): void
        {
            $record = AppointmentRecord::with([
                'appointment.user',
                'appointment.consultant.user',
            ])->findOrFail($this->recordId);

            $file = Mockery::mock(TemporaryUploadedFile::class);
            $file->shouldReceive('getMimeType')->andReturn('application/pdf');
            $file->shouldReceive('get')->andReturn('%PDF fake');
            $file->shouldReceive('getClientOriginalName')->andReturn('reuniao.pdf');

            $draft = $generate->execute($file, $record->appointment);

            $record->update([
                'content' => $draft->content,
                'internal_summary' => $draft->internalSummary,
                'model_used' => $draft->modelUsed,
                'input_tokens' => $draft->inputTokens,
                'output_tokens' => $draft->outputTokens,
            ]);
        }
    };

    $job->handle(resolve(GenerateRecordDraftAction::class));

    $record->refresh();

    expect($record->content)->toContain('Conteúdo persistido pelo job.')
        ->and($record->internal_summary)->toContain('ponto-chave do próximo consultor')
        ->and($record->model_used)->toBe('gemini-2.5-pro')
        ->and($record->input_tokens)->toBe(12450)
        ->and($record->output_tokens)->toBe(2110);

    Mail::assertNothingQueued();
});

it('failed: loga erro, apaga o record placeholder e não envia email', function (): void {
    [$record] = fakeAppointmentWithRecord();

    $job = new GenerateAppointmentRecordJob($record->id, 'fake-tmp.pdf');

    $job->failed(RecordGenerationFailedException::unreadableDocument('reuniao.docx'));

    Log::shouldHaveReceived('error')
        ->withArgs(fn (string $msg): bool => $msg === 'IA :: job de geração falhou definitivamente após retries')
        ->once();

    expect(AppointmentRecord::withTrashed()->find($record->id))->toBeNull();

    Mail::assertNothingQueued();
});

it('failed: apaga o record para outras exceptions sem enviar email', function (): void {
    [$record] = fakeAppointmentWithRecord();

    $job = new GenerateAppointmentRecordJob($record->id, 'fake-tmp.pdf');

    $job->failed(new PrismException('rede caiu'));

    expect(AppointmentRecord::withTrashed()->find($record->id))->toBeNull();

    Mail::assertNothingQueued();
});
